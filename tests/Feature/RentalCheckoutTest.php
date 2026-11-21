<?php

namespace Tests\Feature;

use App\Actions\CreateRentalOrder;
use App\Actions\ReleaseOrderHold;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RentalCheckoutTest extends TestCase
{
    // Separate workers must see committed fixtures; do not wrap this test in RefreshDatabase's transaction.
    use DatabaseMigrations;

    private function customer(string $phone): Pelanggan
    {
        return Pelanggan::forceCreate(['versi_kebijakan' => 'test-v1', 'disetujui_pada' => now(), 'nama' => 'Pemesan', 'nomor_wa' => $phone, 'aktif' => true, 'nomor_terverifikasi_pada' => now()]);
    }

    private function fixture(): array
    {
        config(['checkout.privacy_version' => 'test-v1']);
        $category = DB::table('kategori')->insertGetId(['nama' => 'Papan', 'slug' => 'papan']);
        $product = DB::table('produk')->insertGetId(['kategori_id' => $category, 'nama' => 'Sewa', 'slug' => 'sewa', 'jenis' => 'sewa']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $product, 'nama' => 'A', 'harga' => 250100]);
        DB::table('unit_sewa')->insert(['varian_produk_id' => $variant, 'kode' => 'A-01']);
        $zone = DB::table('zona_antar')->insertGetId(['nama' => 'Dekat', 'jarak_maks_km' => 5, 'ongkir' => 10000]);
        return ['kunci' => (string) Str::uuid(), 'produk_id' => $product, 'varian_id' => $variant, 'tanggal' => today()->addDays(7)->toDateString(),
            'jumlah' => 1, 'pembayaran' => 'dp', 'setuju_privasi' => true, 'versi_privasi' => 'test-v1', 'alamat_antar' => 'Alamat uji', 'zona_antar_id' => $zone];
    }

    public function test_owner_unit_editor_keeps_identity_and_rejects_stale_status(): void
    {
        $fixture = $this->fixture();
        $unit = DB::table('unit_sewa')->first();
        $this->actingAs(Pengguna::factory()->create(['peran'=>'owner']), 'staff');
        $url = route('admin.katalog.units',['id'=>$fixture['produk_id'],'edit'=>$unit->id]);
        $stamp = $this->get($url)->assertOk()->assertSee('A-01')->viewData('stamp');
        $post = route('admin.katalog.manage-units',$fixture['produk_id']);
        $data = ['id'=>$unit->id,'kode'=>'A-01','varian_produk_id'=>$fixture['varian_id'],'status'=>'perawatan','asal'=>'unit','versi_unit'=>$stamp];
        $this->post($post,$data)->assertRedirect($url)->assertSessionHas('success');
        $this->post($post,array_merge($data,['status'=>'aktif']))->assertRedirect($url)->assertSessionHas('unit_warning');
        $this->assertDatabaseHas('unit_sewa',['id'=>$unit->id,'status'=>'perawatan']);
    }

    public function test_create_replay_release_and_rebook(): void
    {
        $data = $this->fixture();
        $customer = $this->customer('628111111111');
        $checkout = app(CreateRentalOrder::class);
        $order = $checkout->create($customer, $data + ['total' => 10]);
        $this->assertSame($order->id, $checkout->create($customer, $data)->id);
        $this->assertSame(261000, $order->total);
        $this->assertSame('BK-'.now()->format('ym').'-0001', $order->nomor);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => $order->id, 'tagihan' => 130500, 'jenis' => 'dp']);
        $this->assertDatabaseHas('item_pesanan', ['pesanan_id' => $order->id, 'harga_satuan' => 250100]);
        try {
            $checkout->create($customer, array_replace($data, ['jumlah' => 2]));
            $this->fail('A changed request must not reuse its key.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Kunci checkout', $e->getMessage());
        }
        $this->assertDatabaseCount('pembayaran', 1);
        $order->update(['tahan_hingga_pada' => now()->subMinute()]);
        $this->assertTrue(app(ReleaseOrderHold::class)->expire($order->id));
        $next = $checkout->create($customer, array_replace($data, ['kunci' => (string) Str::uuid()]));
        $this->assertNotEquals($order->id, $next->id);
        $this->assertDatabaseCount('hari_reservasi', 1);
        $this->assertDatabaseHas('hari_reservasi', ['pesanan_id' => $next->id]);
    }

    public function test_invoice_failure_rolls_back_already_allocated_units_and_number(): void
    {
        $data = $this->fixture();
        $customer = $this->customer('628111111111');
        $checkout = app(CreateRentalOrder::class);
        $previous = $checkout->create($customer, $data);
        // Every pending amount in this invoice range has been claimed. Use a
        // different rental date so the failure occurs AFTER allocating the unit.
        $taken = DB::table('pembayaran')->where('pesanan_id', $previous->id)->value('kode_unik');
        foreach (array_chunk(range(1, 999), 100) as $codes) {
            $rows = [];
            foreach ($codes as $code) {
                if ($code == $taken) {
                    continue;
                }
                $rows[] = ['pesanan_id' => $previous->id, 'jenis' => 'dp', 'tagihan' => 130500, 'kode_unik' => $code,
                    'nominal_diharapkan' => 130500 + $code, 'nominal_tertunda' => 130500 + $code];
            }
            DB::table('pembayaran')->insert($rows);
        }
        try {
            $checkout->create($customer, array_replace($data, ['kunci' => (string) Str::uuid(), 'tanggal' => today()->addDays(8)->toDateString()]));
            $this->fail('Checkout must not keep a reservation without an invoice.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('kode pembayaran', $e->getMessage());
        }
        foreach (['pesanan', 'item_pesanan', 'hari_reservasi', 'checkout_sewa', 'riwayat_status_pesanan'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertDatabaseHas('urutan_nomor_pesanan', ['terakhir' => 1]);
        $this->assertDatabaseCount('pembayaran', 999);
    }

    public function test_fifty_connections_compete_for_last_unit(): void
    {
        $data = $this->fixture();
        $jobs = [];
        foreach (range(1, 50) as $i) {
            $phone = sprintf('62811111%04d', $i);
            $jobs[] = ['op' => 'checkout', 'customer' => $this->customer($phone)->id, 'payload' => $data];
        }
        $outcomes = $this->race($jobs);
        $this->assertSame(1, count(array_keys($outcomes, 'RESERVED', true)));
        $this->assertSame(49, count(array_keys($outcomes, 'UNAVAILABLE', true)));
        $this->artisan('reservations:audit')->assertSuccessful();
        foreach (['pesanan', 'item_pesanan', 'hari_reservasi', 'pembayaran', 'riwayat_status_pesanan', 'checkout_sewa'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertDatabaseHas('urutan_nomor_pesanan', ['terakhir' => 1]);
    }

    public function test_expiry_and_bank_verification_contend_at_the_deadline(): void
    {
        $data = $this->fixture();
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111113'), $data);
        $staff = Pengguna::factory()->create(['peran' => 'admin']);
        $invoice = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        $order->update(['tahan_hingga_pada' => now()->subSecond()]);
        $outcomes = $this->race([
            ['op' => 'expire', 'order' => $order->id],
            ['op' => 'verify', 'staff' => $staff->id, 'order' => $order->id, 'payload' => [
                'pembayaran_id' => $invoice->id, 'referensi' => 'BANK:0001', 'nominal' => $invoice->nominal_diharapkan]],
        ]);
        $this->assertEqualsCanonicalizing(['DONE', 'perlu_rekonsiliasi'], $outcomes);
        $this->assertSame('kedaluwarsa', $order->fresh()->status);
        $this->assertDatabaseCount('hari_reservasi', 0);
        $this->assertDatabaseHas('mutasi_masuk', ['referensi' => 'BANK:0001', 'alasan' => 'lewat_batas']);
        $this->assertDatabaseCount('riwayat_status_pesanan', 2); // T01 + T03, never T02
    }

    public function test_verified_dp_keeps_unit_and_repeat_reference_does_not_credit_twice(): void
    {
        $data = $this->fixture();
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111114'), $data);
        $staff = Pengguna::factory()->create(['peran' => 'admin']);
        $invoice = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        $payload = ['pembayaran_id' => $invoice->id, 'referensi' => 'BANK:0002', 'nominal' => $invoice->nominal_diharapkan];
        config(['checkout.staging' => true]);
        $url = route('admin.pesanan.verify-payment', $order->id);
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->postJson($url, $payload)->assertForbidden();
        $this->actingAs($staff, 'staff')->postJson($url, $payload)->assertOk()->assertJsonPath('hasil', 'terverifikasi');
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, array_replace($payload, ['nominal' => 1]))->assertConflict();
        $order->update(['tahan_hingga_pada' => now()->subMinute()]);
        $this->artisan('pesanan:expire')->assertSuccessful();
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'saldo' => 130500, 'jumlah_dibayar' => 130500, 'status' => 'dikonfirmasi']);
        $this->assertDatabaseHas('pembayaran', ['id' => $invoice->id, 'nominal_diterima' => $payload['nominal'], 'nominal_tertunda' => null]);
        $this->assertDatabaseCount('hari_reservasi', 1);
        $this->assertDatabaseCount('mutasi_masuk', 1);
        $this->assertDatabaseCount('riwayat_status_pesanan', 2);
    }

    public function test_balance_invoice_settles_once_without_resetting_ready_status(): void
    {
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111115'), $this->fixture());
        $dp = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        $staff = Pengguna::factory()->create(['peran' => 'admin']);
        $verify = app(\App\Actions\VerifyRentalTransfer::class);
        $first = ['pembayaran_id' => $dp->id, 'referensi' => 'BANK:DP1', 'nominal' => $dp->nominal_diharapkan];
        $this->assertSame('terverifikasi', $verify->record($staff, $order->id, $first));
        $verify->record($staff, $order->id, $first);
        $this->assertDatabaseCount('pembayaran', 2);
        $bill = DB::table('pembayaran')->where('pesanan_id', $order->id)->where('jenis', 'pelunasan')->sole();
        $this->assertEquals(130500, $bill->tagihan);
        $this->assertNotEquals($dp->nominal_diharapkan, $bill->nominal_diharapkan);
        $order->update(['status' => 'siap']);
        $last = ['pembayaran_id' => $bill->id, 'referensi' => 'BANK:B1', 'nominal' => $bill->nominal_diharapkan];
        $order->update(['batas_pelunasan_pada' => now()->subMinute()]);
        $this->assertSame('perlu_rekonsiliasi', $verify->record($staff, $order->id, array_replace($last, ['referensi' => 'BANK:LATE'])));
        $this->assertSame(130500, $order->fresh()->saldo);
        $this->assertDatabaseCount('hari_reservasi', 1);
        // Restore a future deadline to exercise the on-time branch separately.
        $order->update(['batas_pelunasan_pada' => now()->addHour()]);
        $this->assertSame('terverifikasi', $verify->record($staff, $order->id, $last));
        $verify->record($staff, $order->id, $last);
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'status' => 'siap', 'jumlah_dibayar' => 261000, 'saldo' => 0]);
        $this->assertDatabaseHas('pembayaran', ['id' => $bill->id, 'status' => 'terverifikasi', 'nominal_tertunda' => null]);
        $this->assertDatabaseCount('riwayat_status_pesanan', 2);
    }

    public function test_manual_return_of_uncredited_transfer_is_full_audited_and_idempotent(): void
    {
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111119'), $this->fixture());
        $staff = Pengguna::factory()->create(['peran' => 'admin']);
        $this->actingAs($staff, 'staff');
        $queue = route('admin.rekonsiliasi.index');
        config(['checkout.staging' => false]);
        $this->getJson($queue)->assertServiceUnavailable();
        config(['checkout.staging' => true]);
        $verify = route('admin.pesanan.verify-payment', $order->id);
        $bill = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        $incoming = ['referensi' => 'BANK:IN:A', 'nominal' => 120000, 'pembayaran_id' => $bill->id];
        $this->postJson($verify, $incoming)->assertAccepted();
        $this->postJson($verify, array_replace($incoming, ['referensi' => 'BANK:IN:B']))->assertAccepted();
        $this->getJson($queue)->assertOk()->assertJsonPath('total', 2);
        $this->get($queue,['Accept'=>'text/html'])->assertOk()->assertSee('BANK:IN:A')->assertSee('Catat mutasi keluar');
        $this->get($queue.'?q=IN:A',['Accept'=>'text/html'])->assertOk()->assertSee('BANK:IN:A')->assertDontSee('BANK:IN:B');
        $this->get($queue.'?q=%25',['Accept'=>'text/html'])->assertOk()->assertSee('0 mutasi');
        $id = DB::table('mutasi_masuk')->where('referensi', 'BANK:IN:A')->value('id');
        $url = route('admin.rekonsiliasi.pengembalian', $id);
        $refund = ['referensi_keluar' => 'BANK:OUT:1', 'nominal' => 120000, 'alasan' => 'Nominal transfer tidak cocok; pengembalian dicek pada mutasi keluar.'];
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->postJson($url, $refund)->assertForbidden();
        $this->actingAs($staff, 'staff')->postJson($url, array_replace($refund, ['nominal' => 110000]))->assertConflict();
        $this->postJson($url, $refund)->assertCreated();
        $this->postJson($url, $refund)->assertOk();
        $this->postJson($url, array_replace($refund, ['referensi_keluar' => 'BANK:OUT:2']))->assertConflict();
        $other = DB::table('mutasi_masuk')->where('referensi', 'BANK:IN:B')->value('id');
        $this->postJson(route('admin.rekonsiliasi.pengembalian', $other), $refund)->assertConflict();
        $this->postJson($verify, $incoming)->assertOk()->assertJsonPath('hasil', 'dikembalikan');
        $this->getJson($queue)->assertJsonPath('total', 1);
        $this->get($queue.'?kelompok=dikembalikan',['Accept'=>'text/html'])->assertOk()->assertSee('BANK:OUT:1')->assertDontSee('Catat mutasi keluar untuk');
        $this->assertDatabaseCount('pengembalian_mutasi', 1);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'pengembalian_mutasi', 'subjek_id' => $id, 'pengguna_id' => $staff->id]);
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'saldo' => $order->total, 'jumlah_dibayar' => 0, 'status' => 'menunggu_pembayaran']);
        $this->assertDatabaseHas('pembayaran', ['id' => $bill->id, 'status' => 'menunggu']);
        $this->assertDatabaseCount('hari_reservasi', 1);
    }

    public static function refundCases(): array
    {
        return [
            'H-2 confirmed' => [2, 'dikonfirmasi', 130500],
            'H-1 confirmed' => [1, 'dikonfirmasi', 65250],
            'early in production' => [4, 'sedang_diproduksi', 65250],
            'event today' => [0, 'dikonfirmasi', 0],
            'ready' => [4, 'siap', 0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('refundCases')]
    public function test_paid_cancellation_uses_the_lesser_percentage_and_records_payout_once(int $days, string $status, int $expected): void
    {
        $data = $this->fixture();
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111120'), $data);
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $dp = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        app(\App\Actions\VerifyRentalTransfer::class)->record($admin, $order->id, ['pembayaran_id' => $dp->id, 'referensi' => 'BANK:DP', 'nominal' => $dp->nominal_diharapkan]);
        $order->update(['status' => $status]);
        $this->travelTo($order->tanggal_serah->copy()->subDays($days)->setTime(12, 0));
        config(['checkout.staging' => true]);
        $cancel = route('admin.pesanan.cancel-paid', $order->id);
        $input = ['alasan' => 'Acara dibatalkan pelanggan.'];
        $this->actingAs($admin, 'staff')->postJson($cancel, $input + ['nominal_override' => 1, 'alasan_override' => 'Penyesuaian'])->assertForbidden();
        $this->get(route('admin.pesanan.show',$order->id),['Accept'=>'text/html'])->assertOk()->assertSee('Batalkan setelah konfirmasi');
        $this->postJson($cancel, $input)->assertOk()->assertJsonPath('nominal_refund', $expected);
        $this->postJson($cancel, $input)->assertOk();
        $this->postJson($cancel, ['alasan' => 'Berubah'])->assertConflict();
        $this->assertDatabaseCount('hari_reservasi', 0);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => $order->id, 'jenis' => 'pelunasan', 'status' => 'kedaluwarsa', 'nominal_tertunda' => null]);
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'status' => 'dibatalkan', 'saldo' => 0, 'jumlah_dibayar' => 130500]);
        $this->assertSame(1, DB::table('riwayat_status_pesanan')->where('kode_transisi', 'T12')->count());
        $pay = route('admin.pesanan.record-refund', $order->id);
        $receipt = ['referensi_keluar' => 'BANK:OUT', 'nominal' => max(1, $expected)];
        $detail = route('admin.pesanan.show',$order->id);
        $html = ['Accept'=>'text/html'];
        if ($expected === 0) {
            $this->get($detail,$html)->assertOk()->assertSee('Tidak ada dana yang harus dikembalikan')->assertDontSee('Simpan pencatatan refund');
            $this->assertDatabaseMissing('pembayaran', ['pesanan_id' => $order->id, 'jenis' => 'refund']);
            $this->postJson($pay, $receipt)->assertConflict();
            return;
        }
        $this->get($detail,$html)->assertOk()->assertSee('Menunggu pencatatan mutasi keluar')->assertSee('Simpan pencatatan refund');
        $this->postJson($pay, array_replace($receipt, ['nominal' => 1]))->assertConflict();
        $this->postJson($pay, $receipt)->assertCreated();
        $this->postJson($pay, $receipt)->assertOk();
        $this->postJson($pay, array_replace($receipt, ['referensi_keluar' => 'BANK:OTHER']))->assertConflict();
        $this->get($detail,$html)->assertOk()->assertSee('BANK:OUT')->assertDontSee('Simpan pencatatan refund');
        $this->assertDatabaseCount('referensi_mutasi_keluar', 1);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => $order->id, 'jenis' => 'refund', 'status' => 'terverifikasi', 'nominal_diterima' => $expected]);
    }

    public static function reviewOutcomes(): array
    {
        return [['disetujui'], ['ditolak']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reviewOutcomes')]
    public function test_customer_request_is_scoped_non_financial_and_reviewed_once(string $outcome): void
    {
        $data = $this->fixture();
        $customer = $this->customer('628111111125');
        $order = app(CreateRentalOrder::class)->create($customer, $data);
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $url = route('pesanan.request-cancellation', $order->nomor);
        $reason = ['alasan' => 'Acara tidak jadi.'];
        config(['checkout.staging' => false]);
        $this->actingAs($customer, 'customer')->postJson($url, $reason)->assertServiceUnavailable();
        config(['checkout.staging' => true]);
        $this->postJson($url, $reason)->assertConflict();
        $dp = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        app(\App\Actions\VerifyRentalTransfer::class)->record($admin, $order->id, ['pembayaran_id' => $dp->id, 'referensi' => 'BANK:DP', 'nominal' => $dp->nominal_diharapkan]);
        $this->actingAs($this->customer('628111111126'), 'customer')->postJson($url, $reason)->assertNotFound();
        $this->actingAs($customer, 'customer')->postJson($url, $reason)->assertCreated();
        $this->postJson($url, $reason)->assertOk();
        $this->postJson($url, ['alasan' => 'Berubah'])->assertConflict();
        $this->get(route('pesanan.show', $order->nomor))->assertOk()->assertSee('Menunggu pemeriksaan Admin. Pesanan belum dibatalkan.')->assertSee('Acara tidak jadi.')->assertDontSee('Ajukan pembatalan');
        $this->assertDatabaseCount('permohonan_batal', 1);
        $this->assertSame('dikonfirmasi', $order->fresh()->status);
        $this->assertDatabaseCount('hari_reservasi', 1);
        $queue = route('admin.permohonan-batal.index');
        $approve = route('admin.pesanan.approve-cancellation', $order->id);
        $reject = route('admin.pesanan.reject-cancellation', $order->id);
        $note = ['alasan' => 'Sudah ditinjau.'];
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->getJson($queue)->assertForbidden();
        $this->actingAs($admin, 'staff')->getJson($queue)->assertJsonPath('total', 1);
        $this->get($queue,['Accept'=>'text/html'])->assertOk()->assertSee('Alasan pelanggan: Acara tidak jadi.')->assertSee($order->nomor);
        $accepted = $outcome === 'disetujui';
        $this->post($accepted ? $approve : $reject, $note+['asal'=>'antrean'])->assertRedirect($queue)->assertSessionHas('success');
        $this->postJson($accepted ? $approve : $reject, $note)->assertOk();
        $this->postJson($accepted ? $reject : $approve, $note)->assertConflict();
        $this->getJson($queue)->assertJsonPath('total', 0);
        $this->actingAs($customer, 'customer')->getJson($url)->assertJsonPath('status', $outcome);
        $this->get(route('pesanan.show', $order->nomor))->assertOk()->assertSee(ucfirst($outcome))->assertSee('Sudah ditinjau.')->assertDontSee('Ajukan pembatalan');
        $this->assertDatabaseHas('permohonan_batal', ['pesanan_id' => $order->id, 'peninjau_id' => $admin->id, 'catatan_keputusan' => 'Sudah ditinjau.']);
        $this->assertDatabaseCount('keputusan_refund', $accepted ? 1 : 0);
        $this->assertDatabaseCount('hari_reservasi', $accepted ? 0 : 1);
    }

    public function test_replacement_moves_only_the_reserved_unit_and_preserves_the_original_price(): void
    {
        config(['checkout.staging' => true]);
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111111'), $this->fixture());
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $bill = DB::table('pembayaran')->sole();
        app(\App\Actions\VerifyRentalTransfer::class)->record($admin, $order->id, ['referensi' => 'DP-UNIT', 'nominal' => $bill->nominal_diharapkan, 'pembayaran_id' => $bill->id]);
        $item = $order->items()->sole();
        $new = DB::table('unit_sewa')->insertGetId(['kode' => 'A-02', 'varian_produk_id' => $item->varian_produk_id, 'status' => 'perawatan']);
        $data = ['kunci' => (string) Str::uuid(), 'item_id' => $item->id, 'unit_lama_id' => $item->unit_sewa_id, 'unit_baru_id' => $new, 'alasan' => 'Unit lama perlu diperiksa'];
        $url = route('admin.pesanan.ganti-unit', $order->id);
        $this->actingAs($admin, 'staff')->postJson($url, $data)->assertConflict();
        $this->assertDatabaseHas('hari_reservasi', ['unit_sewa_id' => $item->unit_sewa_id]);
        $screen = route('admin.pesanan.unit', [$order->id,$item->id]);
        $this->get($screen)->assertOk()->assertDontSee('A-02')->assertSee('Tidak ada unit pengganti');
        DB::table('unit_sewa')->where('id', $new)->update(['status' => 'aktif']);
        $this->get($screen)->assertOk()->assertSee('Pilih kode unit')->assertSee('A-02');
        $this->postJson($url, $data)->assertCreated();
        $this->get($screen)->assertOk()->assertSee('Unit yang ditetapkan:')->assertSee('→ A-02')->assertSee($data['alasan']);
        $this->postJson($url, $data)->assertOk();
        $this->postJson($url, array_replace($data, ['kunci' => (string) Str::uuid()]))->assertConflict();
        $this->assertDatabaseCount('penggantian_unit', 1);
        $this->assertDatabaseCount('hari_reservasi', 1);
        $this->assertDatabaseHas('hari_reservasi', ['pesanan_id' => $order->id, 'unit_sewa_id' => $new]);
        $this->assertDatabaseHas('item_pesanan', ['id' => $item->id, 'unit_sewa_id' => $new, 'harga_satuan' => $item->harga_satuan]);
    }

    public function test_same_day_board_rules_unit_block_and_design_lock(): void
    {
        config(['checkout.staging' => true]);
        $this->travelTo(today()->setTime(10, 0));
        $data = array_replace($this->fixture(), ['tanggal' => today()->toDateString(), 'metode_teks' => 'spidol', 'jam_serah' => '13:00', 'pembayaran' => 'penuh']);
        $customer = $this->customer('628111111111');
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $courier = Pengguna::factory()->create(['peran' => 'staf_kurir']);
        $unit = DB::table('unit_sewa')->value('id');
        DB::table('produk')->where('id', $data['produk_id'])->update(['izin_hari_sama' => true]);
        $block = ['tanggal' => $data['tanggal'], 'alasan' => 'Perawatan harian'];
        $blockUrl = route('papan.blokir', $unit);
        $blocked = $this->actingAs($admin, 'staff')->postJson($blockUrl, $block)->assertCreated()->json('blokir_id');
        $this->actingAs($customer, 'customer')->postJson(route('pesanan.store'), $data)->assertConflict();
        $this->assertDatabaseCount('pesanan', 0);
        $this->assertFalse(\App\Models\Produk::bisaDisewaPada($data['tanggal'])->exists());
        $this->actingAs($admin, 'staff')->deleteJson(route('papan.buka', [$unit, $blocked]))->assertNoContent();
        $this->actingAs($customer, 'customer');
        foreach ([['jam_serah' => '12:59'], ['metode_teks' => 'cutting'], ['pembayaran' => 'dp']] as $invalid) {
            $this->postJson(route('pesanan.store'), array_replace($data, $invalid))->assertConflict();
        }
        $this->postJson(route('pesanan.store'), $data)->assertRedirect();
        $order = \App\Models\Pesanan::sole();
        $bill = DB::table('pembayaran')->sole();
        app(\App\Actions\VerifyRentalTransfer::class)->record($admin, $order->id, ['pembayaran_id' => $bill->id, 'referensi' => 'PAPAN', 'nominal' => $bill->nominal_diharapkan]);
        $this->actingAs($admin, 'staff')->postJson($blockUrl, $block)->assertCreated()->assertJsonPath('peringatan_pesanan_aktif.0', $order->nomor);
        $calendar = route('admin.kalender',['tanggal'=>$data['tanggal'],'unit'=>'A-01']);
        $this->get($calendar)->assertOk()->assertSee('Unit memiliki reservasi')->assertSee($order->nomor)->assertSee('Perawatan harian')->assertSee('Buka blokir unit A-01');
        $this->get(route('admin.pengaturan.index'))->assertForbidden();
        $blockId = DB::table('blokir_unit_harian')->where('unit_sewa_id',$unit)->value('id');
        $this->delete(route('papan.buka',[$unit,$blockId]),['asal'=>'kalender-unit','tanggal'=>$data['tanggal'],'konfirmasi'=>1])->assertRedirect($calendar.'#unit-harian');
        $this->assertDatabaseCount('hari_reservasi', 1);
        $text = ['judul' => 'Selamat', 'penerima' => 'Nadia', 'pengirim' => 'Teman'];
        $edit = route('papan.edit', $order->id);
        $this->actingAs($courier, 'staff')->putJson($edit, $text)->assertForbidden();
        $payload = ['pesanan_id' => $order->id];
        $this->actingAs($admin, 'staff')->postJson(route('staf.produksi.start'), $payload)->assertConflict();
        $this->putJson($edit, array_replace($text, ['judul' => str_repeat('a', 26)]))->assertUnprocessable();
        $detail = route('admin.pesanan.show',$order->id);
        $page = $this->get($detail,['Accept'=>'text/html'])->assertOk()->assertSee('Sunting teks papan')->assertSee('Spidol');
        preg_match('/name="versi" value="([a-f0-9]{64})"/', $page->getContent(), $match);
        $this->from($detail)->put($edit,$text+['versi'=>$match[1],'asal'=>'rincian-admin'])->assertRedirect($detail.'#teks-papan');
        $this->putJson($edit,array_replace($text,['judul'=>'Lama','versi'=>$match[1]]))->assertConflict();
        $this->postJson(route('staf.produksi.mark-ready'), $payload)->assertConflict();
        $this->post(route('staf.produksi.start'), $payload)->assertRedirect();
        $this->putJson($edit, $text)->assertConflict();
        $this->post(route('staf.produksi.mark-ready'), $payload)->assertRedirect();
        $this->get($detail,['Accept'=>'text/html'])->assertOk()->assertSee('Nadia')->assertSee('Teks terkunci')->assertDontSee('Sunting teks papan')->assertSee('Riwayat status');
    }

    public static function depositCases(): array
    {
        return [[0], [40000], [100000]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('depositCases')]
    public function test_rental_lifecycle_from_invoice_to_private_inspections_late_fees_and_closeout(int $used): void
    {
        config(['checkout.staging' => true]);
        \Illuminate\Support\Facades\Storage::fake('local');
        $data = $this->fixture();
        $customer = $this->customer('628111111128');
        $order = app(CreateRentalOrder::class)->create($customer, $data);
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $owner = Pengguna::factory()->create(['peran' => 'owner']);
        $courier = Pengguna::factory()->create(['peran' => 'staf_kurir']);
        $bill = DB::table('pembayaran')->where('pesanan_id', $order->id)->first();
        $this->actingAs($customer, 'customer')->getJson(route('payment.show', $order->nomor))->assertOk();
        $account = DB::table('rekening_pembayaran')->insertGetId(['nama_bank' => 'BRI', 'nomor_rekening' => '00123456', 'atas_nama' => 'Uji']);
        $this->postJson(route('payment.confirm', $order->nomor), ['pembayaran_id' => $bill->id, 'tujuan_id' => $account])->assertAccepted();
        $this->assertSame(0, $order->fresh()->jumlah_dibayar);
        $verify = app(\App\Actions\VerifyRentalTransfer::class);
        $verify->record($admin, $order->id, ['pembayaran_id' => $bill->id, 'nominal' => $bill->nominal_diharapkan, 'referensi' => 'BANK:DP']);
        $this->actingAs($admin, 'staff')->post(route('staf.produksi.mark-ready'), ['pesanan_id' => $order->id])->assertRedirect();
        $item = $order->items()->firstOrFail();
        $picture = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('unit.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlWjQAAAABJRU5ErkJggg=='));
        $row = ['item_id' => $item->id, 'kondisi' => 'baik', 'cek' => ['permukaan' => 'baik', 'bunga' => 'tidak_ada', 'easel' => 'baik', 'led' => 'tidak_ada']];
        $handover = fn () => ['pesanan_id' => $order->id, 'jenis_jaminan' => 'deposit', 'nominal_jaminan' => 100000, 'inspeksi' => [$row + ['foto' => [$picture()]]]];
        $this->travelTo($order->tanggal_serah->copy()->setTime(9, 0));
        $this->actingAs($courier, 'staff')->post(route('staf.kurir.handover'), $handover())->assertConflict();
        $this->travelBack();
        $bill = DB::table('pembayaran')->where('jenis','pelunasan')->sole();
        $verify->record($admin, $order->id, ['pembayaran_id' => $bill->id, 'nominal' => $bill->nominal_diharapkan, 'referensi' => 'BANK:BAL']);
        $this->travelTo($order->tanggal_serah->copy()->setTime(9, 0));
        $this->post(route('staf.kurir.handover'), $handover())->assertOk();
        $this->post(route('staf.kurir.handover'), $handover())->assertConflict();
        $this->getJson(route('staf.kurir.index', ['tugas' => 'jemput']))->assertOk()->assertJsonPath('pesanan.data.0.nomor', $order->nomor)->assertJsonPath('pesanan.data.0.tugas','jemput');
        $this->assertDatabaseCount('penguasaan_unit', 1);
        $delta = 0;
        if ($used === 40000) {
            $extension = ['kunci' => (string) Str::uuid(), 'jam' => 1, 'alasan' => 'Diminta pelanggan sebelum batas kembali'];
            $url = route('papan.perpanjang', $order->id);
            $this->postJson($url, $extension)->assertForbidden();
            $detail = route('admin.pesanan.show',$order->id);
            $html = ['Accept'=>'text/html'];
            $this->actingAs($admin,'staff')->get($detail,$html)->assertOk()->assertSee('1 jam · 19:00 WIB · +Rp126.000');
            $extension['jam_sebelum'] = 0;
            $this->postJson($url, $extension)->assertCreated()->assertJsonPath('nominal', 126000);
            $this->postJson($url, $extension)->assertOk();
            $this->postJson($url, array_replace($extension, ['jam' => 2]))->assertConflict();
            $this->postJson($url,array_replace($extension,['kunci'=>(string)Str::uuid()]))->assertConflict();
            $this->get($detail,$html)->assertOk()->assertSee('1 dari 5 jam tambahan terpakai')->assertSee($extension['alasan']);
            $this->assertDatabaseCount('perpanjangan_sewa', 1);
            $this->actingAs($courier, 'staff');
            $delta = 126000 - 25000; // one approved hour replaces one hour of lateness
        }
        $this->travelTo($order->tanggal_serah->copy()->setTime(19, 1));
        $row['kondisi'] = 'rusak';
        $row['cek']['permukaan'] = 'cacat';
        $row['catatan'] = 'Akrilik retak saat kembali';
        $return = fn () => ['pesanan_id' => $order->id, 'inspeksi' => [$row + ['foto' => [$picture()]]]];
        $this->post(route('staf.kurir.receive-return'), $return())->assertOk()->assertJsonPath('saldo', 50000 + $delta);
        $this->post(route('staf.kurir.receive-return'), $return())->assertConflict();
        $this->assertDatabaseHas('unit_sewa', ['id' => $item->unit_sewa_id, 'status' => 'rusak']);
        $this->assertDatabaseCount('penguasaan_unit', 0);
        $this->assertDatabaseCount('foto_inspeksi', 2);
        $photo = DB::table('foto_inspeksi')->first();
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($photo->jalur_berkas);
        $assessment = ['rincian'=>[['kode'=>'akrilik_pecah','jumlah'=>1]], 'biaya_kerusakan' => 200000, 'alasan' => 'Foto menunjukkan akrilik pecah'];
        $evidence = route('retur.inspeksi',$order->id);
        $this->get($evidence,['Accept'=>'text/html'])->assertForbidden();
        $this->actingAs($admin,'staff')->get($evidence,['Accept'=>'text/html'])->assertOk()
            ->assertSeeInOrder(['Saat diserahkan','Kondisi: Baik','Saat dikembalikan','Kondisi: Rusak','Akrilik retak saat kembali'])
            ->assertSee(route('retur.foto',[$order->id,$photo->id]))->assertDontSee($photo->jalur_berkas);
        $this->getJson($evidence)->assertOk()->assertJsonCount(2);
        $this->get(route('retur.foto',[$order->id,$photo->id]))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->postJson(route('retur.nilai', $order->id), $assessment)->assertForbidden();
        $this->get($evidence,['Accept'=>'text/html'])->assertSee('Hanya Owner')->assertDontSee('Simpan penilaian final');
        $this->actingAs($owner,'staff')->get($evidence,['Accept'=>'text/html'])->assertOk()->assertSee('Simpan penilaian final')->assertSee('Akrilik pecah');
        $form = ['asal'=>'bukti-retur','jumlah'=>['akrilik_pecah'=>1,'bunga_rusak'=>0],'diperiksa'=>1,'biaya_kerusakan'=>200000,'alasan'=>$assessment['alasan']];
        $this->postJson(route('retur.nilai',$order->id),array_replace($form,['biaya_kerusakan'=>1]))->assertUnprocessable();
        $this->postJson(route('retur.nilai',$order->id),$form)->assertCreated();
        $invoice = $this->postJson(route('retur.nilai', $order->id), $assessment)->assertOk()->json('tagihan_id');
        $html = ['Accept'=>'text/html'];
        $this->get($evidence,$html)->assertOk()->assertSee('Catat penerimaan setoran deposit')->assertSee('Foto menunjukkan akrilik pecah')->assertDontSee('Catat penutupan pesanan');
        $this->assertSame(250000 + $delta, $order->fresh()->saldo);
        $complete = ['jaminan_dikembalikan' => true, 'nominal_kembali' => 100000, 'catatan' => 'Deposit dikembalikan penuh.'];
        $this->actingAs($admin, 'staff')->postJson(route('retur.selesai', $order->id), $complete)->assertConflict();
        if ($used) {
            $cut = ['nominal' => $used, 'alasan' => 'Mengurangi tagihan akrilik pecah'];
            $url = route('retur.potong', $order->id);
            $this->postJson($url, $cut)->assertForbidden();
            $this->actingAs($owner, 'staff')->postJson($url, $cut)->assertConflict();
            $this->postJson(route('retur.setoran', $order->id), ['nominal' => 100000])->assertOk();
            $this->get($evidence,$html)->assertOk()->assertSee('Simpan pemakaian deposit')->assertSee('Maksimum Rp100.000');
            $this->actingAs($admin,'staff')->get($evidence,$html)->assertSee('Hanya Owner')->assertDontSee('Simpan pemakaian deposit');
            $this->actingAs($owner,'staff');
            $this->postJson($url, array_replace($cut, ['nominal' => 100001]))->assertUnprocessable();
            $payment = $this->postJson($url, $cut)->assertOk()->json('pembayaran_id');
            $this->postJson($url, $cut)->assertOk()->assertJsonPath('pembayaran_id', $payment);
            $this->postJson($url, array_replace($cut, ['alasan' => 'Alasan berbeda']))->assertConflict();
            $this->get($evidence,$html)->assertOk()->assertSee($cut['alasan'])->assertDontSee('Simpan pemakaian deposit');
            $this->assertDatabaseHas('pembayaran', ['id' => $invoice, 'status' => 'kedaluwarsa', 'nominal_tertunda' => null]);
            $invoice = DB::table('penutupan_sewa')->where('pesanan_id', $order->id)->value('tagihan_id');
            $this->assertSame(250000 + $delta - $used, $order->fresh()->saldo);
            $complete = ['jaminan_dikembalikan' => $used < 100000, 'nominal_kembali' => 100000 - $used, 'catatan' => 'Deposit diperhitungkan untuk kerusakan.'];
        }
        $bill = DB::table('pembayaran')->where('id', $invoice)->first();
        $this->assertSame('terverifikasi', $verify->record($admin, $order->id, ['pembayaran_id' => $invoice, 'nominal' => $bill->nominal_diharapkan, 'referensi' => 'BANK:RETUR']));
        if (! $used) {
            $this->postJson(route('retur.selesai', $order->id), $complete)->assertConflict();
            $this->postJson(route('retur.setoran', $order->id), ['nominal' => 100000])->assertOk();
        } else {
            $this->postJson(route('retur.selesai', $order->id), array_replace($complete, ['nominal_kembali' => 100000]))->assertConflict();
        }
        $this->get($evidence,$html)->assertOk()->assertSee('Setoran deposit tercatat')->assertSee('Catat penutupan pesanan')->assertSee('Rp'.number_format(100000-$used,0,',','.'));
        $this->postJson(route('retur.selesai', $order->id), $complete)->assertOk();
        $this->get($evidence,$html)->assertOk()->assertSee('Penutupan tercatat')->assertSee($complete['catatan'])->assertDontSee('Catat penutupan pesanan');
        $this->postJson(route('retur.selesai', $order->id), $complete)->assertOk();
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'status' => 'selesai', 'saldo' => 0, 'jumlah_dibayar' => 511000 + $delta]);
        $this->assertSame(1, DB::table('riwayat_status_pesanan')->where('kode_transisi','T11')->count());
        $this->actingAs($owner, 'staff')->getJson(route('admin.laporan.index', ['period'=>'harian']))->assertOk()->assertJsonPath('neto', (int) $bill->nominal_diharapkan);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lateCases')]
    public function test_late_transfer_resolution_is_atomic_and_does_not_double_credit(bool $taken): void
    {
        config(['checkout.staging' => true]);
        $data = $this->fixture();
        $customer = $this->customer('628111111111');
        $checkout = app(CreateRentalOrder::class);
        $order = $checkout->create($customer, $data);
        $order->update(['tahan_hingga_pada' => now()->subMinute()]);
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $invoice = DB::table('pembayaran')->sole();
        app(\App\Actions\VerifyRentalTransfer::class)->record($admin, $order->id, ['pembayaran_id' => $invoice->id, 'nominal' => $invoice->nominal_diharapkan, 'referensi' => 'LATE']);
        if ($taken) {
            $other = $checkout->create($customer, array_replace($data, ['kunci' => (string) Str::uuid()]));
        }
        $receipt = DB::table('mutasi_masuk')->sole();
        $this->actingAs($admin, 'staff');
        $queue = route('admin.rekonsiliasi.index');
        $html = ['Accept'=>'text/html'];
        $this->get($queue,$html)->assertOk()->assertSee('Proses reaktivasi');
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('admin.pesanan.reactivate-expired', $order->id), ['mutasi_id' => $receipt->id])->assertOk()
                ->assertJsonPath('hasil', $taken ? 'perlu_pengembalian' : 'dikonfirmasi');
        }
        $this->assertDatabaseCount('hari_reservasi', 1);
        $this->assertDatabaseHas('hari_reservasi', ['pesanan_id' => $taken ? $other->id : $order->id]);
        $this->get($taken ? $queue : $queue.'?kelompok=dikreditkan',$html)->assertOk()->assertSee('LATE')->assertDontSee('Proses reaktivasi')
            ->assertSee($taken ? 'Periksa pengembalian penuh' : 'Mutasi sudah dikreditkan');
        $this->assertSame($taken ? 0 : 130500, $order->fresh()->jumlah_dibayar);
        $this->assertSame(1, DB::table('riwayat_status_pesanan')->where('kode_transisi', $taken ? 'T14' : 'T13')->count());
        $this->assertSame($taken ? 0 : 1, DB::table('pembayaran')->where('jenis', 'pelunasan')->count());
        $this->postJson(route('admin.rekonsiliasi.pengembalian', $receipt->id), ['referensi_keluar' => 'OUT', 'nominal' => $receipt->nominal, 'alasan' => 'Dana keluar dicek'])->assertStatus($taken ? 201 : 409);
    }

    public static function lateCases(): array
    {
        return [[false], [true]];
    }

    public function test_catalog_to_in_store_sale_and_pickup(): void
    {
        config(['checkout.staging' => true]);
        $fixture = $this->fixture();
        $customer = $this->customer('628111111111');
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $owner = Pengguna::factory()->create(['peran' => 'owner']);
        $product = DB::table('produk')->insertGetId(['kategori_id' => DB::table('kategori')->value('id'), 'nama' => 'Buket', 'slug' => 'jual', 'jenis' => 'jual']);
        $variant = ['nama' => 'Merah', 'harga' => 25500, 'stok' => 1, 'aktif' => true];
        $url = route('admin.katalog.manage-variants', $product);
        $this->actingAs($admin, 'staff')->postJson($url, $variant)->assertForbidden();
        $vid = $this->actingAs($owner, 'staff')->postJson($url, $variant)->assertOk()->json('id');
        $variantPage = route('admin.katalog.variants',['id'=>$product,'edit'=>$vid]);
        $stamp = $this->get($variantPage)->assertOk()->assertSee('Stok tersedia')->viewData('stamp');
        $this->postJson(route('admin.katalog.manage-variants', $fixture['produk_id']), ['id' => $vid, 'nama' => 'Salah', 'harga' => 100, 'aktif' => true])->assertNotFound();
        $data = ['pelanggan_id' => $customer->id, 'produk_id' => $product, 'varian_id' => $vid, 'kunci' => (string) Str::uuid(), 'nominal' => 26000,
            'jumlah' => 1, 'versi_privasi' => 'test-v1', 'setuju_privasi' => true];
        $this->actingAs($admin, 'staff')->postJson(route('toko.store'), array_replace($data, ['nominal' => 1]))->assertConflict();
        $this->get(route('toko.form',['pelanggan'=>$customer->id]))->assertOk()->assertSee('Varian siap jual')->assertSee('Rp25.500');
        $id = $this->postJson(route('toko.store'), $data)->assertCreated()->json('pesanan_id');
        $form = $data + ['asal_form'=>'toko','tunai_diperiksa'=>1];
        unset($form['produk_id']);
        $this->post(route('toko.store'),$form)->assertRedirect(route('admin.pesanan.show',$id))->assertSessionHas('manual_success');
        $this->postJson(route('toko.store'), $data)->assertOk()->assertJsonPath('pesanan_id', $id);
        $this->postJson(route('toko.store'), array_replace($data, ['kunci' => (string) Str::uuid()]))->assertConflict();
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertDatabaseHas('varian_produk', ['id' => $vid, 'stok' => 0]);
        $this->actingAs($owner,'staff')->postJson($url,$variant+['id'=>$vid,'versi_varian'=>$stamp])->assertConflict();
        $this->assertDatabaseHas('varian_produk', ['id'=>$vid,'stok'=>0]);
        $this->actingAs($admin,'staff');
        $this->postJson(route('toko.selesai', $id))->assertConflict();
        $this->post(route('staf.produksi.mark-ready'), ['pesanan_id' => $id])->assertRedirect();
        $this->postJson(route('toko.serah', $id), ['barang_diperiksa' => true, 'diterima_pembeli' => true])->assertOk();
        $this->get(route('admin.pesanan.show',$id),['Accept'=>'text/html'])->assertOk()->assertSee('Selesaikan pesanan nonsewa');
        $this->postJson(route('toko.selesai', $id))->assertOk();
        $this->get(route('admin.pesanan.show',$id),['Accept'=>'text/html'])->assertOk()->assertDontSee('Selesaikan pesanan nonsewa');
        $this->postJson(route('toko.selesai', $id))->assertOk();
        $this->assertDatabaseCount('riwayat_status_pesanan', 5);
        $this->assertDatabaseHas('pesanan', ['id' => $id, 'saldo' => 0, 'status' => 'selesai', 'jumlah_dibayar' => 26000]);
        $dates = ['mulai' => today()->toDateString(), 'akhir' => today()->toDateString()];
        $this->getJson(route('laporan.penerimaan', $dates))->assertForbidden();
        $this->actingAs($owner, 'staff')->getJson(route('laporan.penerimaan', $dates))->assertOk()->assertJsonPath('neto', 26000)->assertJsonPath('rincian.0.kanal', 'tunai');
        $csv = $this->get(route('laporan.penerimaan.csv', $dates))->assertOk()->streamedContent();
        $this->assertStringContainsString('tunai,26000,0,26000', $csv);
        $this->assertStringNotContainsString($customer->nomor_wa, $csv);
    }

    public function test_staff_unpaid_cancel_requires_clear_payment_and_releases_once(): void
    {
        config(['checkout.staging'=>true]);
        $order = app(CreateRentalOrder::class)->create($this->customer('628111111111'),$this->fixture());
        $staff = Pengguna::factory()->create(['peran'=>'admin']);
        $url = route('admin.pesanan.cancel-unpaid',$order->id);
        $input = ['konfirmasi'=>1,'alasan'=>'Pelanggan tidak jadi memesan'];
        $this->actingAs(Pengguna::factory()->create(['peran'=>'staf_produksi']),'staff')->postJson($url,$input)->assertForbidden();
        $this->actingAs($staff,'staff');
        $bill = DB::table('pembayaran')->where('pesanan_id',$order->id);
        $bill->update(['dikonfirmasi_pelanggan_pada'=>now()]);
        $this->postJson($url,$input)->assertConflict();
        $this->assertDatabaseCount('hari_reservasi',1);
        $bill->update(['dikonfirmasi_pelanggan_pada'=>null]);
        $this->postJson($url,$input)->assertOk();
        $this->postJson($url,$input)->assertOk();
        $this->postJson($url,array_replace($input,['alasan'=>'Berbeda']))->assertConflict();
        $this->assertDatabaseCount('hari_reservasi',0);
        $this->assertSame(1,DB::table('riwayat_status_pesanan')->where(['kode_transisi'=>'T04','pengguna_id'=>$staff->id,'catatan'=>$input['alasan']])->count());
        $this->assertDatabaseCount('keputusan_refund',0);
    }

    public function test_manual_wa_order_uses_rental_rules_and_records_staff_identity(): void
    {
        config(['checkout.staging' => true]);
        $customer = $this->customer('628111111111');
        $data = $this->fixture() + ['pelanggan_id' => $customer->id, 'persetujuan_pelanggan_diperiksa' => true];
        $url = route('admin.pesanan.manual-store');
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_produksi']), 'staff')->postJson($url, $data)->assertForbidden();
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $this->actingAs($admin, 'staff')->postJson($url, $data + ['alasan_pengecualian' => 'Lewati stok'])->assertUnprocessable();
        $formUrl = route('admin.pesanan.manual-sewa',['pelanggan'=>$customer->id]);
        $this->post(route('admin.pesanan.manual-pelanggan'),['nomor_wa'=>'0'.substr($customer->nomor_wa,2)])->assertRedirect($formUrl);
        $this->get($formUrl)->assertOk()->assertSee('Pilih varian sewa');
        $input = $data + ['asal_form'=>'manual-sewa'];
        unset($input['produk_id']);
        $this->from($formUrl)->post($url,$input)->assertSessionHas('manual_success');
        $id = DB::table('pesanan')->sole()->id;
        $this->post($url,$input)->assertRedirect(route('admin.pesanan.show',$id));
        $this->postJson($url, $data)->assertOk()->assertJsonPath('pesanan_id', $id);
        $this->getJson(route('admin.pesanan.index'))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('admin.pesanan.show', $id))->assertOk()->assertJsonPath('asal', 'admin');
        $this->assertDatabaseHas('riwayat_status_pesanan', ['pesanan_id' => $id, 'pelaku' => 'staf', 'pengguna_id' => $admin->id]);
        $this->assertDatabaseCount('hari_reservasi', 1);
    }

    public function test_twenty_carts_compete_for_last_production_slot_without_partial_stock_debit(): void
    {
        $rental = $this->fixture();
        $cat = DB::table('kategori')->value('id');
        $sale = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'jual', 'nama' => 'Siap', 'slug' => 'siap']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $sale, 'nama' => 'Biru', 'stok' => 2, 'harga' => 10000]);
        $custom = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'custom', 'nama' => 'Buket', 'slug' => 'buket', 'harga_dasar' => 20000]);
        DB::table('kapasitas_produksi_harian')->insert(['tanggal' => $rental['tanggal'], 'kelompok_kapasitas' => 'buket_custom', 'batas' => 1]);
        $cart = ['tanggal' => $rental['tanggal'], 'versi_privasi' => 'test-v1', 'setuju_privasi' => true, 'cara_serah' => 'ambil', 'pembayaran' => 'penuh',
            'items' => [['produk_id' => $sale, 'varian_id' => $variant, 'jumlah' => 1], ['produk_id' => $custom, 'jumlah' => 1]]];
        $jobs = [];
        foreach (range(1, 20) as $i) {
            $phone = sprintf('62812341%04d', $i);
            $jobs[] = ['op' => 'bouquet', 'customer' => $this->customer($phone)->id, 'payload' => $cart + ['kunci' => (string) Str::uuid()]];
        }
        $outcomes = $this->race($jobs);
        $this->assertSame(1, count(array_keys($outcomes, 'RESERVED', true)));
        $this->assertSame(19, count(array_keys($outcomes, 'UNAVAILABLE', true)));
        $this->assertDatabaseCount('checkout_buket', 1);
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertDatabaseHas('varian_produk', ['id' => $variant, 'stok' => 1]);
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 1]);
    }

    public function test_fifty_successful_simultaneous_checkouts_have_distinct_monthly_numbers(): void
    {
        $data = $this->fixture();
        $jobs = [];
        foreach (range(1, 50) as $i) {
            $jobs[] = ['op' => 'checkout', 'customer' => $this->customer(sprintf('62811111%04d', $i))->id,
                'payload' => array_replace($data, ['tanggal' => today()->addDays(7 + $i)->toDateString()])];
        }
        $this->assertSame(array_fill(0, 50, 'RESERVED'), $this->race($jobs));
        $this->assertSame(50, DB::table('pesanan')->distinct()->count('nomor'));
        $this->assertDatabaseHas('urutan_nomor_pesanan', ['terakhir' => 50]);
        $this->artisan('reservations:audit')->assertSuccessful();
    }

    private function race(array $jobs): array
    {
        $workers = [];
        $path = tempnam(sys_get_temp_dir(), 'rental-race-');
        $barrier = fopen($path, 'rb');
        flock($barrier, LOCK_EX);
        try {
            foreach ($jobs as $job) {
                $input = json_encode($job + ['barrier' => $path,
                    'database' => config('database.connections.'.DB::getDefaultConnection())], JSON_THROW_ON_ERROR)."\n";
                $worker = new Process([PHP_BINARY, base_path('tests/Support/rental-checkout-worker.php')], base_path(), ['APP_ENV' => 'testing'], $input, 120);
                $worker->start();
                $workers[] = $worker;
                $this->assertTrue($worker->waitUntil(fn ($type, $output) => str_contains($output, 'READY')), $worker->getErrorOutput());
            }
            // Both independent connections are ready: release one OS-level barrier.
            flock($barrier, LOCK_UN);
            $outcomes = [];
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait(), $worker->getErrorOutput());
                $outcomes[] = trim(str_replace('READY', '', $worker->getOutput()));
            }
            return $outcomes;
        } finally {
            foreach ($workers as $worker) {
                $worker->stop();
            }
            fclose($barrier);
            unlink($path);
        }
    }
}

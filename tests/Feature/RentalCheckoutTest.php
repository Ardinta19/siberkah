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
        return Pelanggan::create(['nama' => 'Pemesan', 'nomor_wa' => $phone, 'aktif' => true, 'nomor_terverifikasi_pada' => now()]);
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

    public function test_two_connections_compete_for_last_unit(): void
    {
        $data = $this->fixture();
        $jobs = [];
        foreach (['628111111111', '628111111112'] as $phone) {
            $jobs[] = ['op' => 'checkout', 'customer' => $this->customer($phone)->id, 'payload' => $data];
        }
        $outcomes = $this->race($jobs);
        $this->assertEqualsCanonicalizing(['RESERVED', 'UNAVAILABLE'], $outcomes);
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
        $this->postJson($cancel, $input)->assertOk()->assertJsonPath('nominal_refund', $expected);
        $this->postJson($cancel, $input)->assertOk();
        $this->postJson($cancel, ['alasan' => 'Berubah'])->assertConflict();
        $this->assertDatabaseCount('hari_reservasi', 0);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => $order->id, 'jenis' => 'pelunasan', 'status' => 'kedaluwarsa', 'nominal_tertunda' => null]);
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'status' => 'dibatalkan', 'saldo' => 0, 'jumlah_dibayar' => 130500]);
        $this->assertSame(1, DB::table('riwayat_status_pesanan')->where('kode_transisi', 'T12')->count());
        $pay = route('admin.pesanan.record-refund', $order->id);
        $receipt = ['referensi_keluar' => 'BANK:OUT', 'nominal' => max(1, $expected)];
        if ($expected === 0) {
            $this->assertDatabaseMissing('pembayaran', ['pesanan_id' => $order->id, 'jenis' => 'refund']);
            $this->postJson($pay, $receipt)->assertConflict();
            return;
        }
        $this->postJson($pay, array_replace($receipt, ['nominal' => 1]))->assertConflict();
        $this->postJson($pay, $receipt)->assertCreated();
        $this->postJson($pay, $receipt)->assertOk();
        $this->postJson($pay, array_replace($receipt, ['referensi_keluar' => 'BANK:OTHER']))->assertConflict();
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
        $this->assertDatabaseCount('permohonan_batal', 1);
        $this->assertSame('dikonfirmasi', $order->fresh()->status);
        $this->assertDatabaseCount('hari_reservasi', 1);
        $queue = route('admin.permohonan-batal.index');
        $approve = route('admin.pesanan.approve-cancellation', $order->id);
        $reject = route('admin.pesanan.reject-cancellation', $order->id);
        $note = ['alasan' => 'Sudah ditinjau.'];
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->getJson($queue)->assertForbidden();
        $this->actingAs($admin, 'staff')->getJson($queue)->assertJsonPath('total', 1);
        $accepted = $outcome === 'disetujui';
        $this->postJson($accepted ? $approve : $reject, $note)->assertOk();
        $this->postJson($accepted ? $approve : $reject, $note)->assertOk();
        $this->postJson($accepted ? $reject : $approve, $note)->assertConflict();
        $this->getJson($queue)->assertJsonPath('total', 0);
        $this->actingAs($customer, 'customer')->getJson($url)->assertJsonPath('status', $outcome);
        $this->assertDatabaseHas('permohonan_batal', ['pesanan_id' => $order->id, 'peninjau_id' => $admin->id, 'catatan_keputusan' => 'Sudah ditinjau.']);
        $this->assertDatabaseCount('keputusan_refund', $accepted ? 1 : 0);
        $this->assertDatabaseCount('hari_reservasi', $accepted ? 0 : 1);
    }

    public function test_rental_lifecycle_from_invoice_to_private_inspections_late_fees_and_closeout(): void
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
        $this->postJson(route('payment.confirm', $order->nomor), ['pembayaran_id' => $bill->id])->assertAccepted();
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
        $this->assertDatabaseCount('penguasaan_unit', 1);
        $this->travelTo($order->tanggal_serah->copy()->setTime(19, 1));
        $row['kondisi'] = 'rusak';
        $row['cek']['permukaan'] = 'cacat';
        $row['catatan'] = 'Akrilik retak saat kembali';
        $return = fn () => ['pesanan_id' => $order->id, 'inspeksi' => [$row + ['foto' => [$picture()]]]];
        $this->post(route('staf.kurir.receive-return'), $return())->assertOk()->assertJsonPath('saldo', 50000);
        $this->post(route('staf.kurir.receive-return'), $return())->assertConflict();
        $this->assertDatabaseHas('unit_sewa', ['id' => $item->unit_sewa_id, 'status' => 'rusak']);
        $this->assertDatabaseCount('penguasaan_unit', 0);
        $this->assertDatabaseCount('foto_inspeksi', 2);
        $photo = DB::table('foto_inspeksi')->first();
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($photo->jalur_berkas);
        $assessment = ['biaya_kerusakan' => 200000, 'alasan' => 'Foto menunjukkan akrilik pecah'];
        $this->actingAs($admin, 'staff')->postJson(route('retur.nilai', $order->id), $assessment)->assertForbidden();
        $this->actingAs($owner, 'staff')->postJson(route('retur.nilai', $order->id), $assessment)->assertCreated();
        $invoice = $this->postJson(route('retur.nilai', $order->id), $assessment)->assertOk()->json('tagihan_id');
        $this->assertSame(250000, $order->fresh()->saldo);
        $complete = ['jaminan_dikembalikan' => true, 'nominal_kembali' => 100000, 'catatan' => 'Deposit dikembalikan penuh.'];
        $this->actingAs($admin, 'staff')->postJson(route('retur.selesai', $order->id), $complete)->assertConflict();
        $bill = DB::table('pembayaran')->where('id', $invoice)->first();
        $this->assertSame('terverifikasi', $verify->record($admin, $order->id, ['pembayaran_id' => $invoice, 'nominal' => $bill->nominal_diharapkan, 'referensi' => 'BANK:RETUR']));
        $this->postJson(route('retur.selesai', $order->id), $complete)->assertConflict();
        $this->postJson(route('retur.setoran', $order->id), ['nominal' => 100000])->assertOk();
        $this->postJson(route('retur.selesai', $order->id), $complete)->assertOk();
        $this->postJson(route('retur.selesai', $order->id), $complete)->assertOk();
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'status' => 'selesai', 'saldo' => 0, 'jumlah_dibayar' => 511000]);
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
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('admin.pesanan.reactivate-expired', $order->id), ['mutasi_id' => $receipt->id])->assertOk()
                ->assertJsonPath('hasil', $taken ? 'perlu_pengembalian' : 'dikonfirmasi');
        }
        $this->assertDatabaseCount('hari_reservasi', 1);
        $this->assertDatabaseHas('hari_reservasi', ['pesanan_id' => $taken ? $other->id : $order->id]);
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
        $this->postJson(route('admin.katalog.manage-variants', $fixture['produk_id']), ['id' => $vid, 'nama' => 'Salah', 'harga' => 100, 'aktif' => true])->assertNotFound();
        $data = ['pelanggan_id' => $customer->id, 'produk_id' => $product, 'varian_id' => $vid, 'kunci' => (string) Str::uuid(), 'nominal' => 26000,
            'jumlah' => 1, 'versi_privasi' => 'test-v1', 'setuju_privasi' => true];
        $this->actingAs($admin, 'staff')->postJson(route('toko.store'), array_replace($data, ['nominal' => 1]))->assertConflict();
        $id = $this->postJson(route('toko.store'), $data)->assertCreated()->json('pesanan_id');
        $this->postJson(route('toko.store'), $data)->assertOk()->assertJsonPath('pesanan_id', $id);
        $this->postJson(route('toko.store'), array_replace($data, ['kunci' => (string) Str::uuid()]))->assertConflict();
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertDatabaseHas('varian_produk', ['id' => $vid, 'stok' => 0]);
        $this->postJson(route('toko.selesai', $id))->assertConflict();
        $this->post(route('staf.produksi.mark-ready'), ['pesanan_id' => $id])->assertRedirect();
        $this->postJson(route('toko.serah', $id), ['barang_diperiksa' => true, 'diterima_pembeli' => true])->assertOk();
        $this->postJson(route('toko.selesai', $id))->assertOk();
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

    public function test_manual_wa_order_uses_rental_rules_and_records_staff_identity(): void
    {
        config(['checkout.staging' => true]);
        $customer = $this->customer('628111111111');
        $data = $this->fixture() + ['pelanggan_id' => $customer->id, 'persetujuan_pelanggan_diperiksa' => true];
        $url = route('admin.pesanan.manual-store');
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_produksi']), 'staff')->postJson($url, $data)->assertForbidden();
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $this->actingAs($admin, 'staff')->postJson($url, $data + ['alasan_pengecualian' => 'Lewati stok'])->assertUnprocessable();
        $id = $this->postJson($url, $data)->assertOk()->json('pesanan_id');
        $this->postJson($url, $data)->assertOk()->assertJsonPath('pesanan_id', $id);
        $this->getJson(route('admin.pesanan.index'))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('admin.pesanan.show', $id))->assertOk()->assertJsonPath('asal', 'admin');
        $this->assertDatabaseHas('riwayat_status_pesanan', ['pesanan_id' => $id, 'pelaku' => 'staf', 'pengguna_id' => $admin->id]);
        $this->assertDatabaseCount('hari_reservasi', 1);
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
                $worker = new Process([PHP_BINARY, base_path('tests/Support/rental-checkout-worker.php')], base_path(), ['APP_ENV' => 'testing'], $input, 20);
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

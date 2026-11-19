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

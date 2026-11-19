<?php

namespace Tests\Feature;

use App\Actions\ReleaseOrderHold;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReleaseOrderHoldTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Pesanan
    {
        $customer = Pelanggan::create(['nomor_wa' => '628111234567']);
        return Pesanan::create(['nomor' => 'BK-2611-0001', 'pelanggan_id' => $customer->id,
            'nama_pemesan' => 'Pembeli', 'nomor_wa_pemesan' => $customer->nomor_wa, 'cara_serah' => 'antar',
            'tanggal_serah' => today()->addDay(), 'status' => 'menunggu_pembayaran', 'tahan_hingga_pada' => now()->subMinute()]);
    }

    public function test_expiry_releases_unit_and_pending_amount_only_once(): void
    {
        $order = $this->order();
        $category = DB::table('kategori')->insertGetId(['nama' => 'Papan', 'slug' => 'papan']);
        $product = DB::table('produk')->insertGetId(['nama' => 'A', 'slug' => 'a', 'kategori_id' => $category, 'jenis' => 'sewa']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $product, 'nama' => 'A', 'harga' => 75000]);
        $unit = DB::table('unit_sewa')->insertGetId(['varian_produk_id' => $variant, 'kode' => 'A1']);
        $item = DB::table('item_pesanan')->insertGetId(['pesanan_id' => $order->id, 'produk_id' => $product, 'varian_produk_id' => $variant,
            'unit_sewa_id' => $unit, 'nama_produk' => 'A', 'harga_satuan' => 75000, 'subtotal' => 75000]);
        DB::table('hari_reservasi')->insert(['pesanan_id' => $order->id, 'unit_sewa_id' => $unit, 'item_pesanan_id' => $item, 'tanggal' => today()->addDay()]);
        DB::table('pembayaran')->insert(['pesanan_id' => $order->id, 'jenis' => 'penuh', 'tagihan' => 75000, 'kode_unik' => 12,
            'nominal_diharapkan' => 75012, 'nominal_tertunda' => 75012]);

        $this->artisan('pesanan:expire')->assertSuccessful();
        $this->artisan('pesanan:expire')->assertSuccessful();
        $this->assertDatabaseMissing('hari_reservasi', ['pesanan_id' => $order->id]);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => $order->id, 'nominal_tertunda' => null, 'status' => 'kedaluwarsa']);
        $this->assertSame('kedaluwarsa', $order->fresh()->status);
        $this->assertDatabaseCount('riwayat_status_pesanan', 1);
    }

    public function test_future_hold_and_received_funds_are_not_released(): void
    {
        $order = $this->order();
        $order->update(['tahan_hingga_pada' => now()->addHour()]);
        $holds = app(ReleaseOrderHold::class);
        $this->assertFalse($holds->expire($order->id));
        $order->update(['jumlah_dibayar' => 10000, 'tahan_hingga_pada' => now()->subMinute()]);
        $this->artisan('pesanan:expire')->assertFailed();
        $this->assertSame('menunggu_pembayaran', $order->fresh()->status);
        $this->assertDatabaseCount('riwayat_status_pesanan', 0);
    }

    public function test_sale_stock_is_credited_once_and_only_with_a_hold_receipt(): void
    {
        $order = $this->order();
        $category = DB::table('kategori')->insertGetId(['nama' => 'Buket', 'slug' => 'buket']);
        $product = DB::table('produk')->insertGetId(['nama' => 'B', 'slug' => 'b', 'kategori_id' => $category, 'jenis' => 'jual']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $product, 'nama' => 'B', 'harga' => 25000, 'stok' => 4]);
        $item = DB::table('item_pesanan')->insertGetId(['pesanan_id' => $order->id, 'produk_id' => $product, 'varian_produk_id' => $variant,
            'nama_produk' => 'B', 'harga_satuan' => 25000, 'jumlah' => 2, 'subtotal' => 50000]);
        $this->artisan('pesanan:expire')->assertFailed();
        $this->assertDatabaseHas('varian_produk', ['id' => $variant, 'stok' => 4]);
        DB::table('stok_ditahan')->insert(['item_pesanan_id' => $item, 'varian_produk_id' => $variant, 'jumlah' => 2]);
        $this->artisan('pesanan:expire')->assertSuccessful();
        $this->artisan('pesanan:expire')->assertSuccessful();
        $this->assertDatabaseHas('varian_produk', ['id' => $variant, 'stok' => 6]);
        $this->assertDatabaseCount('stok_ditahan', 0);
        $this->assertDatabaseCount('riwayat_status_pesanan', 1);
    }

    public function test_customer_can_only_cancel_own_unpaid_hold(): void
    {
        $order = $this->order();
        $other = Pelanggan::create(['nomor_wa' => '628111234568']);
        $holds = app(ReleaseOrderHold::class);
        try {
            $holds->cancel($other, $order->nomor);
            $this->fail('Cross-customer cancellation must not resolve an order.');
        } catch (ModelNotFoundException) {
            $this->assertSame('menunggu_pembayaran', $order->fresh()->status);
        }
        $holds->cancel($order->pelanggan, $order->nomor);
        $holds->cancel($order->pelanggan, $order->nomor);
        $this->assertDatabaseHas('riwayat_status_pesanan', ['pesanan_id' => $order->id, 'kode_transisi' => 'T04']);
        $this->assertDatabaseCount('riwayat_status_pesanan', 1);
    }
}

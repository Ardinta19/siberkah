<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BouquetCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): array
    {
        config(['checkout.staging' => true, 'checkout.privacy_version' => 'v1']);
        $this->actingAs(Pelanggan::create(['nama' => 'Penguji', 'nomor_wa' => '628111111111', 'aktif' => true, 'nomor_terverifikasi_pada' => now()]), 'customer');
        $cat = DB::table('kategori')->insertGetId(['nama' => 'Buket', 'slug' => 'buket']);
        $sale = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'jual', 'nama' => 'Siap', 'slug' => 'siap']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $sale, 'nama' => 'Putih', 'harga' => 40000, 'stok' => 3]);
        $custom = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'custom', 'nama' => 'Custom', 'slug' => 'custom', 'harga_dasar' => 100000]);
        $option = DB::table('opsi_produk')->insertGetId(['produk_id' => $custom, 'nama' => 'Bunga', 'tipe' => 'satuan', 'wajib' => true]);
        $value = DB::table('nilai_opsi')->insertGetId(['opsi_produk_id' => $option, 'label' => 'Mawar', 'harga_tambahan' => 5000]);
        return ['kunci' => (string) Str::uuid(), 'tanggal' => today()->addDays(5)->toDateString(), 'cara_serah' => 'ambil', 'pembayaran' => 'dp', 'versi_privasi' => 'v1', 'setuju_privasi' => true,
            'items' => [['produk_id' => $sale, 'varian_id' => $variant, 'jumlah' => 1], ['produk_id' => $custom, 'jumlah' => 2, 'opsi' => [['id' => $option, 'nilai_id' => $value, 'satuan' => 3]]]]];
    }

    public function test_mixed_cart_from_checkout_through_payment_production_and_handover(): void
    {
        $d = $this->cart();
        $url = route('pesanan.buket');
        $number = $this->postJson($url, $d + ['total' => 1])->assertOk()->assertJsonPath('total', 270000)->json('nomor');
        $this->postJson($url, $d)->assertOk()->assertJsonPath('nomor', $number);
        $this->postJson($url, array_replace($d, ['pembayaran' => 'penuh']))->assertConflict();
        $order = Pesanan::sole();
        $this->getJson(route('payment.show', $number))->assertOk()->assertJsonPath('saldo', 270000);
        $this->assertDatabaseHas('opsi_item_pesanan', ['harga_tambahan' => 15000, 'label_nilai' => 'Mawar']);
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 2]);
        $this->assertDatabaseHas('varian_produk', ['stok' => 2]);
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff');
        foreach (['dp', 'pelunasan'] as $type) {
            $bill = DB::table('pembayaran')->where('jenis', $type)->sole();
            $this->postJson(route('admin.pesanan.verify-payment', $order->id), ['referensi' => 'BANK:'.$type, 'pembayaran_id' => $bill->id,
                'nominal' => $bill->nominal_diharapkan])->assertOk()->assertJsonPath('hasil', 'terverifikasi');
        }
        $payload = ['pesanan_id' => $order->id];
        $this->post(route('staf.produksi.mark-ready'), $payload)->assertConflict();
        $this->post(route('staf.produksi.start'), $payload)->assertRedirect();
        $this->post(route('staf.produksi.mark-ready'), $payload)->assertRedirect();
        $proof = ['barang_diperiksa' => true, 'diterima_pembeli' => true];
        $this->postJson(route('toko.serah', $order->id), $proof)->assertConflict();
        $this->travelTo($order->tanggal_serah);
        $this->postJson(route('toko.serah', $order->id), $proof)->assertOk();
        $this->postJson(route('toko.selesai', $order->id))->assertOk();
        $this->assertSame('selesai', $order->fresh()->status);
    }

    public function test_invalid_options_and_capacity_failure_roll_back_and_cancellation_restores_once(): void
    {
        $d = $this->cart();
        $url = route('pesanan.buket');
        $bad = $d;
        unset($bad['items'][1]['opsi']);
        $this->postJson($url, $bad)->assertUnprocessable();
        DB::table('kapasitas_produksi_harian')->insert(['tanggal' => $d['tanggal'], 'kelompok_kapasitas' => 'buket_custom', 'batas' => 2]);
        $number = $this->postJson($url, $d)->assertOk()->json('nomor');
        $this->postJson($url, array_replace($d, ['kunci' => (string) Str::uuid()]))->assertConflict();
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertDatabaseHas('varian_produk', ['stok' => 2]);
        $this->post(route('pesanan.cancel', $number))->assertRedirect();
        $this->post(route('pesanan.cancel', $number))->assertRedirect();
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 0]);
        $this->assertDatabaseHas('varian_produk', ['stok' => 3]);
        $this->assertDatabaseCount('stok_ditahan', 0);
    }
}

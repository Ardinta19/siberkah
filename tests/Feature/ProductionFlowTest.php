<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_order_requires_production_and_duplicate_ready_does_not_repeat_history(): void
    {
        $staff = Pengguna::factory()->create();
        $customer = Pelanggan::create(['nomor_wa' => '628123456789']);
        $category = DB::table('kategori')->insertGetId(['nama' => 'Buket', 'slug' => 'buket']);
        $product = DB::table('produk')->insertGetId(['kategori_id' => $category, 'nama' => 'Custom', 'slug' => 'custom', 'jenis' => 'custom']);
        $order = Pesanan::create(['nomor' => 'BK-2611-0001', 'pelanggan_id' => $customer->id, 'status' => 'dikonfirmasi',
            'nama_pemesan' => 'Pembeli', 'nomor_wa_pemesan' => $customer->nomor_wa, 'cara_serah' => 'ambil', 'tanggal_serah' => '2026-12-01']);
        DB::table('item_pesanan')->insert(['pesanan_id' => $order->id, 'produk_id' => $product, 'nama_produk' => 'Custom', 'harga_satuan' => 100000, 'subtotal' => 100000]);
        $data = ['pesanan_id' => $order->id];
        $this->actingAs($staff, 'staff');
        $this->post(route('staf.produksi.mark-ready'), $data)->assertConflict();
        $this->post(route('staf.produksi.start'), $data)->assertRedirect();
        $this->post(route('staf.produksi.mark-ready'), $data)->assertRedirect();
        $this->post(route('staf.produksi.mark-ready'), $data)->assertConflict();
        $this->assertSame('siap', $order->fresh()->status);
        $this->assertDatabaseCount('riwayat_status_pesanan', 2);
        $this->assertDatabaseHas('riwayat_status_pesanan', ['kode_transisi' => 'T07', 'pengguna_id' => $staff->id, 'pesanan_id' => $order->id]);
    }
}

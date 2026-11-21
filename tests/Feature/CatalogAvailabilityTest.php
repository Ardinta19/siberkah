<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_only_literal_product_names(): void
    {
        $category = DB::table('kategori')->insertGetId(['nama'=>'Wisuda','slug'=>'wisuda']);
        foreach (['Mawar 100%', 'Melati'] as $i => $name) {
            DB::table('produk')->insert(['nama'=>$name,'slug'=>'produk-'.$i,'kategori_id'=>$category,'jenis'=>'jual']);
        }
        foreach (['Wisuda'=>[], '%'=>['Mawar 100%'], 'Keranjang'=>[], 'awa'=>['Mawar 100%']] as $term=>$names) {
            $this->get(route('catalog.index',['q'=>$term]))->assertOk()
                ->assertViewHas('produk', fn ($page) => $page->pluck('nama')->all() === $names);
        }
    }

    public function test_date_filter_only_exposes_available_rental_variants_and_suggests_unblocked_days(): void
    {
        $cat = DB::table('kategori')->insertGetId(['nama' => 'Papan', 'slug' => 'papan']);
        $p = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'nama' => 'Papan Utama', 'slug' => 'utama', 'jenis' => 'sewa']);
        DB::table('produk')->insert(['kategori_id' => $cat, 'nama' => 'Barang siap jual', 'slug' => 'jual', 'jenis' => 'jual']);
        foreach (['Bening' => 'aktif', 'Merah' => 'perawatan'] as $name => $state) {
            $v = DB::table('varian_produk')->insertGetId(['produk_id' => $p, 'nama' => $name, 'harga' => 75000]);
            DB::table('unit_sewa')->insert(['varian_produk_id' => $v, 'kode' => $name, 'status' => $state]);
        }
        $day = today()->addWeek();
        $url = route('catalog.show', ['slug' => 'utama', 'tanggal' => $day->toDateString()]);
        $this->get(route('catalog.index', ['tanggal' => $day->toDateString(), 'urut'=>'harga_naik']))->assertOk()->assertSee('Rp75.000')->assertSee('1 varian')->assertDontSee('Barang siap jual');
        $this->get($url)->assertOk()->assertSee('Bening')->assertDontSee('Merah');
        $staff = \App\Models\Pengguna::factory()->create();
        foreach ([0, 2] as $offset) {
            DB::table('blokir_kalender')->insert(['tanggal' => $day->copy()->addDays($offset), 'produk_id' => $offset ? null : $p, 'pengguna_id' => $staff->id, 'alasan' => 'Tutup']);
        }
        $this->get($url)->assertOk()->assertViewHas('saran', array_map(fn ($offset) => $day->copy()->addDays($offset)->toDateString(), [1,3,4]));
        DB::table('kategori')->where('id', $cat)->update(['aktif' => false]);
        $this->get($url)->assertNotFound();
    }
}

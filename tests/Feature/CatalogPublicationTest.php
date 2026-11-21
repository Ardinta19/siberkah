<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_publish_archive_and_price_have_atomic_history(): void
    {
        config(['checkout.staging'=>true, 'checkout.privacy_version'=>'v1']);
        $d = ['nama'=>'Uji Draf','slug'=>'uji-draf','jenis'=>'jual','harga_dasar'=>10000,'aktif'=>true,
            'kategori_id'=>DB::table('kategori')->insertGetId(['nama'=>'Kategori','slug'=>'kategori'])];
        $owner = Pengguna::factory()->create(['peran'=>'owner']);
        $this->actingAs($owner,'staff')->post(route('admin.katalog.store'),$d)->assertRedirect();
        $p = Produk::sole();
        $this->assertFalse($p->aktif);
        $this->get(route('admin.katalog.index'))->assertOk()->assertSee('Uji Draf');
        $this->get(route('admin.katalog.edit',$p->id))->assertOk();
        $this->get(route('catalog.index'))->assertDontSee('Uji Draf');
        $this->get(route('catalog.show',$p->slug))->assertNotFound();
        $customer = Pelanggan::forceCreate(['nama'=>'Pembeli','nomor_wa'=>'628100000001','aktif'=>true,'nomor_terverifikasi_pada'=>now(),'versi_kebijakan'=>'v1','disetujui_pada'=>now()]);
        $this->actingAs($customer,'customer')->post(route('checkout.submit',$p->slug),[])->assertNotFound();
        $this->actingAs($owner,'staff')->post(route('admin.katalog.publish',$p->id))->assertRedirect();
        $this->get(route('catalog.show',$p->slug))->assertOk();
        $this->get(route('admin.katalog.index',['tampil'=>'publik']))->assertOk()->assertSee('Uji Draf');
        DB::table('kategori')->where('id',$d['kategori_id'])->update(['aktif'=>false]);
        $this->get(route('admin.katalog.index',['tampil'=>'publik']))->assertOk()->assertDontSee('Uji Draf');
        $this->get(route('admin.katalog.index',['tampil'=>'kategori']))->assertOk()->assertSee('Tersembunyi · kategori nonaktif');
        DB::table('kategori')->where('id',$d['kategori_id'])->update(['aktif'=>true]);
        unset($d['aktif']);
        $d['harga_dasar'] = 15000;
        $this->put(route('admin.katalog.update',$p->id),$d)->assertRedirect();
        $this->assertTrue($p->fresh()->aktif);
        $this->post(route('admin.katalog.archive',$p->id))->assertRedirect();
        $this->post(route('admin.katalog.archive',$p->id))->assertRedirect();
        $this->get(route('catalog.show',$p->slug))->assertNotFound();
        $this->assertSame(4,DB::table('log_aktivitas')->where('subjek_tabel','produk')->count());
    }
}

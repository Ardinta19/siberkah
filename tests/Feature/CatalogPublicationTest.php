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

    public function test_profile_completion_resumes_cart_checkout_without_creating_order(): void
    {
        config(['checkout.staging'=>true,'checkout.privacy_version'=>'v1']);
        $category = DB::table('kategori')->insertGetId(['nama'=>'Buket','slug'=>'buket']);
        Produk::create(['nama'=>'Mawar','slug'=>'mawar','jenis'=>'jual','kategori_id'=>$category,'harga_dasar'=>100,'aktif'=>true]);
        $customer = Pelanggan::forceCreate(['nomor_wa'=>'628100000001','aktif'=>true,'nomor_terverifikasi_pada'=>now()]);
        $url = route('checkout.product',['slug'=>'mawar','tanggal'=>'2030-01-01','koleksi'=>['q'=>'Mawar'],'dari_keranjang'=>1]);
        $this->actingAs($customer,'customer')->get($url)->assertRedirect();
        $token = session('checkout.onboarding.token');
        $this->assertNotEmpty($token);
        $this->get(route('profile.edit',['lanjut'=>$token]))->assertOk()->assertSee('Pesanan belum dibuat');
        $this->put(route('profile.update'),['nama'=>'Pelanggan','lanjut'=>$token,'setuju_privasi'=>true,'versi_privasi'=>'v1'])
            ->assertRedirect($url)->assertSessionMissing('checkout.onboarding');
        $this->assertDatabaseCount('pesanan',0);
    }

    public function test_owner_name_search_is_literal_and_context_survives_navigation(): void
    {
        $owner = Pengguna::factory()->create(['peran'=>'owner']);
        $category = DB::table('kategori')->insertGetId(['nama'=>'Kategori sama','slug'=>'kategori-sama']);
        $a = Produk::create(['nama'=>'Diskon 0%','slug'=>'diskon','jenis'=>'jual','kategori_id'=>$category,'harga_dasar'=>100,'aktif'=>false]);
        Produk::create(['nama'=>'Diskon 01','slug'=>'diskon-01','jenis'=>'jual','kategori_id'=>$category,'harga_dasar'=>100,'aktif'=>false]);
        $context = ['q'=>'0%','tampil'=>'nonaktif'];
        $this->actingAs($owner,'staff')->get(route('admin.katalog.index',$context))->assertOk()
            ->assertSee('Diskon 0%')->assertDontSee('Diskon 01')->assertSee('Hapus pencarian')->assertSee('Reset filter');
        foreach (['edit','variants','gallery'] as $section) {
            $this->get(route('admin.katalog.'.$section,['id'=>$a->id]+$context))->assertOk()
                ->assertSee(route('admin.katalog.index',$context).'#kelola-'.$a->id);
        }
        $this->get(route('admin.katalog.index',['q'=>'Kategori sama']))->assertDontSee('Diskon 01');
        $this->post(route('admin.katalog.publish',['id'=>$a->id]+$context))->assertRedirect(route('admin.katalog.index',$context));
        $this->get(route('admin.katalog.index',$context))->assertDontSee('Diskon 0%');
    }

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
        $this->get(route('admin.katalog.edit',$p->id))->assertOk()->assertSee('Kategori (nonaktif)');
        $this->get(route('admin.katalog.index',['tampil'=>'kategori']))->assertOk()->assertSee('Tersembunyi · kategori nonaktif');
        DB::table('kategori')->where('id',$d['kategori_id'])->update(['aktif'=>true]);
        unset($d['aktif']);
        $editor = route('admin.katalog.edit',['id'=>$p->id,'tampil'=>'publik']);
        $stamp = $this->get($editor)->viewData('editorStamp');
        $d['harga_dasar'] = 15000;
        $this->put(route('admin.katalog.update',$p->id),$d)->assertRedirect();
        $stale = array_merge($d,['harga_dasar'=>12000,'versi_editor'=>$stamp]);
        $target = route('admin.katalog.update',['id'=>$p->id,'tampil'=>'publik']);
        $this->put($target,$stale)->assertRedirect($editor)->assertSessionHas('editor_conflict');
        $this->assertEquals(15000,$p->fresh()->harga_dasar);
        $this->put($target,$d+['versi_editor'=>$this->get($editor)->viewData('editorStamp')])->assertRedirect();
        $this->assertTrue($p->fresh()->aktif);
        $this->post(route('admin.katalog.archive',$p->id))->assertRedirect();
        $this->post(route('admin.katalog.archive',$p->id))->assertRedirect();
        $this->get(route('catalog.show',$p->slug))->assertNotFound();
        $this->assertSame(4,DB::table('log_aktivitas')->where('subjek_tabel','produk')->count());
    }
}

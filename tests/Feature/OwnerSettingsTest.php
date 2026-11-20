<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Support\BusinessSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_update_zone_fee_and_conflicting_old_value_is_rejected(): void
    {
        config(['checkout.staging' => true]);
        $id = \Illuminate\Support\Facades\DB::table('zona_antar')->insertGetId(['nama'=>'Zona','jarak_maks_km'=>5,'ongkir'=>10000]);
        $url = route('pengaturan.ongkir',$id);
        $d = ['sebelum'=>10000,'ongkir'=>12000];
        $this->actingAs(Pengguna::factory()->create(['peran'=>'admin']), 'staff')->patchJson($url,$d)->assertForbidden();
        $this->actingAs(Pengguna::factory()->create(['peran'=>'owner']), 'staff')->patchJson($url,$d)->assertNoContent();
        $this->patchJson($url,$d)->assertNoContent();
        $this->patchJson($url,array_replace($d,['ongkir'=>13000]))->assertConflict();
        $this->getJson(route('admin.aktivitas.index'))->assertJsonPath('total',1)->assertJsonPath('data.0.sebelum.ongkir',10000)->assertJsonPath('data.0.sesudah.ongkir',12000);
    }

    public function test_settings_and_audit_are_owner_only_with_bounded_effective_values(): void
    {
        $payload = ['kunci' => 'tahan_normal_menit', 'nilai' => 45];
        foreach (['admin','staf_produksi','staf_kurir'] as $role) {
            $this->actingAs(Pengguna::factory()->create(['peran' => $role]), 'staff');
            $this->getJson(route('admin.aktivitas.index'))->assertForbidden();
            $this->getJson(route('admin.pengaturan.index'))->assertForbidden();
            $this->postJson(route('admin.pengaturan.update'), $payload)->assertForbidden();
        }
        $this->actingAs(Pengguna::factory()->create(['peran' => 'owner']), 'staff');
        $this->getJson(route('admin.pengaturan.index'))->assertOk()->assertJsonPath('pengaturan.tahan_normal_menit.nilai', 60);
        $this->postJson(route('admin.pengaturan.update'), array_replace($payload, ['nilai' => 0]))->assertUnprocessable();
        $this->postJson(route('admin.pengaturan.update'), $payload)->assertRedirect();
        $this->postJson(route('admin.pengaturan.update'), $payload)->assertRedirect();
        $this->assertSame(45, BusinessSettings::value('tahan_normal_menit'));
        $this->getJson(route('admin.aktivitas.index'))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.sesudah.tahan_normal_menit', 45);
        $this->artisan('privasi:tinjau-retensi')->expectsOutputToContain('Tidak ada data yang dihapus')->assertSuccessful();
    }
}

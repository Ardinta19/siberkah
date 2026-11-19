<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdentityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_logout_and_role_boundary(): void
    {
        $staff = Pengguna::factory()->create(['peran' => 'staf_kurir']);
        $this->post(route('staff.login.post'), ['email' => $staff->surel, 'password' => 'password'])
            ->assertRedirect(route('staf.kurir.index'));
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->get(route('staf.produksi.index'))->assertForbidden();
        $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));
        $this->assertGuest('staff');
    }

    public function test_admin_cannot_change_owner_settings(): void
    {
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff')
            ->post(route('admin.pengaturan.update'))->assertForbidden();
    }

    public function test_missing_provider_does_not_claim_delivery(): void
    {
        config(['services.whatsapp.token' => null]);
        $this->post(route('otp.issue'), ['nomor_wa' => '081234567890'])->assertServiceUnavailable();
        $this->assertDatabaseCount('kode_otp', 0);
    }

    public function test_customer_must_verify_the_session_challenge(): void
    {
        config(['services.whatsapp' => ['token' => 'test', 'phone_id' => '123', 'template' => 'test', 'language' => 'id', 'version' => 'v1.0']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'accepted']]])]);
        $this->post(route('otp.issue'), ['nomor_wa' => '081234567890'])->assertSessionHas('otp.challenge');
        $challenge = session('otp.challenge');
        $code = Http::recorded()->first()[0]['template']['components'][0]['parameters'][0]['text'];
        $this->post(route('otp.verify'), ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->assertGuest('customer');
        $this->post(route('otp.verify'), ['kode' => $code])->assertRedirect(route('catalog.index'));
        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('pelanggan', ['nomor_wa' => '6281234567890']);
        $this->post(route('otp.logout'));
        $this->withSession(['otp.challenge' => $challenge])->post(route('otp.verify'), ['kode' => $code])->assertSessionHasErrors('kode');
        $this->assertGuest('customer');
    }

    public function test_customer_updates_only_their_profile_and_cannot_submit_unimplemented_checkout(): void
    {
        $other = Pelanggan::create(['nomor_wa' => '628123456780', 'aktif' => true, 'nama' => 'Lama']);
        $customer = Pelanggan::create(['nomor_wa' => '628123456781', 'aktif' => true]);
        $this->actingAs($customer, 'customer')->put(route('profile.update'), ['id' => $other->id, 'nama' => 'Baru'])->assertRedirect();
        $this->assertSame('Lama', $other->fresh()->nama);
        $this->assertSame('Baru', $customer->fresh()->nama);
        $this->post(route('pesanan.store'))->assertServiceUnavailable();
        $this->assertDatabaseCount('pesanan', 0);
    }
}

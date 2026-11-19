<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioned_owner_must_rotate_before_using_private_routes(): void
    {
        $email = 'owner@example.test';
        $this->artisan('staf:buat', ['surel' => $email, 'peran' => 'owner', 'nama' => 'Pemilik'])
            ->expectsQuestion('Kata sandi sementara (12–72 byte)', 'temporary-pass')
            ->expectsQuestion('Ulangi kata sandi sementara', 'temporary-pass')->assertSuccessful();
        $this->post(route('staff.login.post'), ['email' => $email, 'password' => 'temporary-pass'])->assertRedirect(route('staff.password.form'));
        $this->get(route('staff.password.form'))->assertOk();
        $this->postJson(route('admin.pengaturan.update'))->assertForbidden();
        $this->post(route('staff.password.change'), ['current_password' => 'temporary-pass', 'new_password' => 'another-password', 'new_password_confirmation' => 'another-password'])->assertRedirect(route('staff.login'));
        $this->assertGuest('staff');
        $this->post(route('staff.login.post'), ['email' => $email, 'password' => 'temporary-pass'])->assertSessionHasErrors('email');
        $this->post(route('staff.login.post'), ['email' => $email, 'password' => 'another-password'])->assertRedirect(route('dashboard.hari-ini'));
    }

    public function test_deactivation_denies_login_but_preserves_last_active_owner(): void
    {
        $owner = Pengguna::factory()->create(['peran' => 'owner']);
        $this->artisan('staf:nonaktif', ['surel' => $owner->surel])->assertFailed();
        $staff = Pengguna::factory()->create();
        $this->artisan('staf:nonaktif', ['surel' => $staff->surel])->assertSuccessful();
        $this->assertFalse($staff->fresh()->aktif);
        $this->post(route('staff.login.post'), ['email' => $staff->surel, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($staff->fresh(), 'staff')->get(route('staf.produksi.index'))->assertForbidden();
    }
}

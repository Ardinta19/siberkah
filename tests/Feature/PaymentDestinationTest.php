<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PaymentDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_publish_and_retire_destinations(): void
    {
        config(['checkout.staging' => true]);
        $bank = ['jenis' => 'bank', 'nama_bank' => 'BRI', 'nomor_rekening' => '00123456', 'atas_nama' => 'Uji'];
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff')->postJson(route('tujuan.store'), $bank)->assertForbidden();
        $this->actingAs(Pengguna::factory()->create(['peran' => 'owner']), 'staff');
        $id = $this->postJson(route('tujuan.store'), $bank + ['aktif' => true])->assertCreated()->assertJsonPath('aktif', false)->json('id');
        $this->patchJson(route('tujuan.status', $id), ['aktif' => true])->assertNoContent();
        $this->assertDatabaseHas('rekening_pembayaran', ['id' => $id, 'aktif' => true, 'nomor_rekening' => '00123456']);
        $this->patchJson(route('tujuan.status', $id), ['aktif' => false])->assertNoContent();
        $qr = ['jenis' => 'qris', 'atas_nama' => 'Uji'];
        $this->postJson(route('tujuan.store'), $qr)->assertUnprocessable();
        $id = $this->postJson(route('tujuan.store'), $qr + ['qris' => UploadedFile::fake()->image('uji.png', 64, 64)])->assertCreated()->json('id');
        $this->assertDatabaseHas('log_aktivitas', ['subjek_id' => $id, 'aksi' => 'buat_tujuan_bayar']);
    }
}

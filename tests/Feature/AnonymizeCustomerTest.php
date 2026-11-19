<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnonymizeCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymization_needs_recent_session_otp_and_preserves_closed_accounting(): void
    {
        config(['checkout.staging' => true]);
        $customer = Pelanggan::create(['nama' => 'Penguji', 'nomor_wa' => '628111111111', 'aktif' => true, 'nomor_terverifikasi_pada' => now()]);
        $other = Pelanggan::create(['nama' => 'Lain', 'nomor_wa' => '628111111112', 'aktif' => true]);
        $order = Pesanan::create(['nomor' => 'BK-2611-0001', 'pelanggan_id' => $customer->id, 'nama_pemesan' => $customer->nama,
            'nomor_wa_pemesan' => $customer->nomor_wa, 'cara_serah' => 'antar', 'alamat_antar' => 'Alamat pribadi', 'tanggal_serah' => today(),
            'status' => 'siap', 'total' => 50000, 'jumlah_dibayar' => 50000]);
        $url = route('profile.destroy');
        $data = ['konfirmasi' => 'HAPUS AKUN SAYA', 'pelanggan_id' => $other->id];
        $this->actingAs($customer, 'customer')->deleteJson($url, $data)->assertForbidden();
        $this->withSession(['otp.verified' => ['id' => $customer->id, 'at' => now()->timestamp]])->deleteJson($url, $data)->assertConflict();
        $order->update(['status' => 'selesai']);
        $bill = DB::table('pembayaran')->insertGetId(['pesanan_id' => $order->id, 'jenis' => 'refund', 'status' => 'menunggu', 'tagihan' => 1000, 'nominal_diharapkan' => 1000]);
        $this->deleteJson($url, $data)->assertConflict();
        DB::table('pembayaran')->where('id', $bill)->update(['status' => 'terverifikasi']);
        $this->deleteJson($url, $data)->assertNoContent();
        $this->assertGuest('customer');
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'total' => 50000, 'jumlah_dibayar' => 50000, 'alamat_antar' => null, 'nomor_wa_pemesan' => '-']);
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertFalse($customer->fresh()->isActive());
        $this->assertNull($customer->fresh()->nama);
        $this->assertDatabaseMissing('pelanggan', ['nomor_wa' => '628111111111']);
        $this->assertDatabaseHas('pelanggan', ['id' => $other->id, 'nama' => 'Lain']);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'anonimisasi_akun', 'pelanggan_id' => $customer->id]);
    }
}

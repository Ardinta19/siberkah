<?php

namespace App\Actions;

use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;

/** Remove account identifiers without deleting the accounting ledger. */
final class AnonymizeCustomer
{
    public function handle(int $customerId): void
    {
        DB::transaction(function () use ($customerId) {
            // Checkouts (including manual orders) lock this same customer before allocating inventory.
            $customer = Pelanggan::lockForUpdate()->findOrFail($customerId);
            abort_unless($customer->isActive() && $customer->isVerified(), 403);
            $orders = DB::table('pesanan')->where('pelanggan_id', $customerId)->orderBy('id')->lockForUpdate()->get(['id', 'status']);
            abort_if($orders->contains(fn ($o) => ! in_array($o->status, ['selesai','dibatalkan','kedaluwarsa'], true)), 409, 'Selesaikan pesanan aktif sebelum menghapus akun.');
            $ids = $orders->pluck('id');
            $bills = DB::table('pembayaran')->whereIn('pesanan_id', $ids);
            abort_if((clone $bills)->where('status', 'menunggu')->exists() || DB::table('mutasi_masuk')->whereIn('pembayaran_id', (clone $bills)->select('id'))
                ->where('hasil', 'perlu_rekonsiliasi')->exists() || DB::table('permohonan_batal')->whereIn('pesanan_id', $ids)->where('status', 'menunggu')->exists(),
                409, 'Masih ada tagihan, refund, atau keputusan yang belum selesai.');

            DB::table('desain_papan')->whereIn('pesanan_id', $ids)->update(['judul' => null, 'penerima' => null, 'pengirim' => null]);
            $items = DB::table('item_pesanan')->whereIn('pesanan_id', $ids);
            DB::table('opsi_item_pesanan')->whereIn('item_pesanan_id', (clone $items)->select('id'))->whereNull('nilai_opsi_id')->update(['teks_bebas' => null]);
            $items->update(['tulisan_kustom' => null]);
            DB::table('permohonan_batal')->whereIn('pesanan_id', $ids)->update(['alasan' => 'Dianonimkan']);
            DB::table('pesanan')->whereIn('id', $ids)->update(['nama_pemesan' => 'Pelanggan anonim', 'nomor_wa_pemesan' => '-',
                'alamat_antar' => null, 'catatan_pelanggan' => null]);
            DB::table('kode_otp')->where('nomor_wa', $customer->nomor_wa)->delete();
            $customer->update(['nama' => null, 'nomor_wa' => 'X'.bin2hex(random_bytes(7)), 'aktif' => false,
                'nomor_terverifikasi_pada' => null, 'masuk_terakhir_pada' => null, 'dihapus_pada' => now()]);
            DB::table('log_aktivitas')->insert(['aksi' => 'anonimisasi_akun', 'pelanggan_id' => $customerId, 'dibuat_pada' => now()]);
        }, 3);
    }
}

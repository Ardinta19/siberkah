<?php

namespace App\Actions;

use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

/** T12 approves a liability, not an outgoing bank transfer. Rental staging only. */
final class CancelPaidRental
{
    public function decide(Pengguna $actor, int $id, string $reason, ?int $override, ?string $overrideReason, bool $fromRequest = false): int
    {
        abort_unless($actor->aktif && $actor->canVerifyPayment(), 403);
        abort_if($override !== null && ! $actor->isOwner(), 403);
        $hash = hash('sha256', json_encode([$reason, $override, $overrideReason], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($id, $actor, $reason, $override, $overrideReason, $hash, $fromRequest) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_bank')->where('pesanan_id', $id)->exists(), 404);
            if ($fromRequest) {
                $status = DB::table('permohonan_batal')->where('pesanan_id', $id)->value('status');
                abort_if($status === null, 404);
                abort_if($status === 'ditolak', 409, 'Permohonan telah ditolak.');
            }
            $decision = DB::table('keputusan_refund')->where('pesanan_id', $id)->first();
            if ($decision) {
                abort_unless($decision->sidik === $hash, 409, 'Keputusan pembatalan sudah tercatat.');
                return (int) $decision->nominal_final;
            }
            $from = $order->status;
            $stagePercent = match ($from) {
                'dikonfirmasi' => 100, 'sedang_diproduksi' => 50, 'siap' => 0,
                default => abort(409, 'Status pesanan tidak mengizinkan pembatalan T12.'),
            };
            $paid = (int) DB::table('pembayaran')->where('pesanan_id', $id)->whereIn('jenis', ['dp', 'pelunasan', 'penuh'])
                ->where('status', 'terverifikasi')->sum('tagihan');
            abort_unless($paid > 0 && $paid === $order->jumlah_dibayar && $paid + $order->saldo === $order->total, 409, 'Saldo tidak cocok. Periksa pembayaran terlebih dahulu.');
            $now = now('Asia/Jakarta');
            $days = $now->copy()->startOfDay()->diffInDays($order->tanggal_serah->copy()->startOfDay(), false);
            $percent = min($stagePercent, $days >= 2 ? 100 : ($days >= 1 ? 50 : 0));
            $calculated = intdiv($paid * $percent, 100);
            $final = $override ?? $calculated;
            abort_unless($final >= 0 && $final <= $paid, 422, 'Refund melebihi pokok yang dibayar.');
            app(ReleaseOrderInventory::class)->release($order, $from === 'dikonfirmasi');
            DB::table('pembayaran')->where('pesanan_id', $id)->where('status', 'menunggu')->whereIn('jenis', ['dp','pelunasan','penuh'])
                ->update(['status' => 'kedaluwarsa', 'nominal_tertunda' => null, 'diubah_pada' => $now]);
            $bill = $final === 0 ? null : DB::table('pembayaran')->insertGetId(['pesanan_id' => $id, 'jenis' => 'refund', 'status' => 'menunggu',
                'tagihan' => $final, 'nominal_diharapkan' => $final, 'dibuat_pada' => $now, 'diubah_pada' => $now]);
            $order->update(['status' => 'dibatalkan', 'saldo' => 0, 'dibatalkan_pada' => $now, 'alasan_batal' => $reason,
                'persen_refund' => $percent, 'nominal_refund' => $final]);
            DB::table('keputusan_refund')->insert(['pesanan_id' => $id, 'pengguna_id' => $actor->id, 'pembayaran_refund_id' => $bill,
                'pokok_dibayar' => $paid, 'nominal_sistem' => $calculated, 'nominal_final' => $final, 'alasan_override' => $overrideReason,
                'sidik' => $hash, 'diputuskan_pada' => $now]);
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $id, 'status_lama' => $from, 'status_baru' => 'dibatalkan',
                'kode_transisi' => 'T12', 'pelaku' => 'staf', 'pengguna_id' => $actor->id, 'catatan' => $reason, 'dibuat_pada' => $now]);
            DB::table('permohonan_batal')->where('pesanan_id', $id)->where('status', 'menunggu')->update([
                'status' => 'disetujui', 'peninjau_id' => $actor->id, 'catatan_keputusan' => $reason, 'diputuskan_pada' => $now,
            ]);
            return $final;
        }, 3);
    }
}

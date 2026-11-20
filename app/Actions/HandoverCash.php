<?php

namespace App\Actions;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Called under the checkout or handover transaction, never a stand-alone credit. */
final class HandoverCash
{
    public function arrange(Pesanan $order, bool $dp): void
    {
        $order->tunai_saat_serah = true;
        if (! $dp) {
            $order->status = 'dikonfirmasi';
            $order->dikonfirmasi_pada = now();
            $order->tahan_hingga_pada = null;
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $order->id, 'status_lama' => 'menunggu_pembayaran',
                'status_baru' => 'dikonfirmasi', 'kode_transisi' => 'T02', 'pelaku' => 'sistem', 'catatan' => 'Tunai saat serah, belum dibayar', 'dibuat_pada' => now()]);
        } else {
            $order->batas_pelunasan_pada = null; // DP still has its original hold deadline.
        }
        $order->save();
    }

    public function collect(Request $request, Pesanan $order): void
    {
        if (! $order->tunai_saat_serah) {
            abort_if($request->hasAny(['tunai_diterima', 'nominal_tunai']), 422, 'Pesanan ini bukan pembayaran tunai saat serah.');
            return;
        }
        $cash = $request->validate(['tunai_diterima' => 'accepted', 'nominal_tunai' => 'required|integer|min:1']);
        abort_unless($order->status === 'siap' && $order->tanggal_serah->isToday() && $order->saldo > 0
            && (int) $cash['nominal_tunai'] === $order->saldo && $order->saldo + $order->jumlah_dibayar === $order->total, 409, 'Nominal atau waktu serah tidak sesuai.');
        abort_if($order->wajib_dp && ! DB::table('pembayaran')->where(['pesanan_id' => $order->id, 'jenis' => 'dp', 'status' => 'terverifikasi'])->exists(), 409);
        $now = now();
        DB::table('pembayaran')->insert(['pesanan_id' => $order->id, 'jenis' => $order->wajib_dp ? 'pelunasan' : 'penuh', 'status' => 'terverifikasi',
            'tunai_saat_serah' => true, 'tagihan' => $order->saldo, 'nominal_diharapkan' => $order->saldo, 'nominal_diterima' => $order->saldo,
            'pengguna_id' => $request->user('staff')->id, 'diterima_pada' => $now, 'diverifikasi_pada' => $now, 'dibuat_pada' => $now, 'diubah_pada' => $now]);
        $order->update(['jumlah_dibayar' => $order->total, 'saldo' => 0]);
    }
}

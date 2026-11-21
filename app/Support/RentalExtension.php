<?php

namespace App\Support;

use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

final class RentalExtension
{
    public static function terms(Pesanan $order, int $used): array
    {
        $end = $order->tanggal_serah->copy()->setTime(18, 0)->addHours($used);
        $rate = $order->items->sum(fn ($item) => intdiv($item->harga_satuan + 1999, 2000) * 1000);
        $reason = match (true) {
            $order->status !== 'sudah_diserahkan' => 'Perpanjangan hanya tersedia saat unit masih diserahkan kepada pelanggan.',
            $order->total !== $order->jumlah_dibayar + $order->saldo => 'Posisi pembayaran perlu diperiksa.',
            now('Asia/Jakarta')->gt($end) => 'Batas kembali sudah lewat; perpanjangan tidak boleh berlaku surut.',
            $used >= 5 => 'Batas lima jam tambahan sudah tercapai.',
            default => null,
        };
        if ($reason === null) {
            $held = DB::table('penguasaan_unit')->where('pesanan_id',$order->id)->pluck('unit_sewa_id');
            if ($order->items->isEmpty() || $held->count() !== $order->items->count() || !$order->items->every(fn ($i) => $held->contains($i->unit_sewa_id) && $i->jumlah == 1)) {
                $reason = 'Penguasaan unit belum cocok dengan rincian pesanan.';
            }
        }
        return compact('end','rate','reason','used');
    }
}

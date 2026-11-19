<?php

namespace App\Actions;

use App\Models\Pesanan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Called inside T13's transaction, after locking order + observed payment. No partial reservations on T14. */
final class RebookBouquet
{
    public function reserve(Pesanan $order, object $receipt, Carbon $now): bool
    {
        $day = $order->tanggal_serah->toDateString();
        if ($day < $now->toDateString()) {
            return false;
        }
        $items = $order->items()->orderBy('produk_id')->orderBy('varian_produk_id')->orderBy('id')->get();
        abort_if($items->isEmpty() || DB::table('stok_ditahan')->whereIn('item_pesanan_id', $items->pluck('id'))->exists(), 409, 'Reservasi lama belum bersih.');
        $products = DB::table('produk')->whereIn('id', $items->pluck('produk_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $variants = DB::table('varian_produk')->whereIn('id', $items->pluck('varian_produk_id')->filter())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if (DB::table('blokir_kalender')->where('tanggal', $day)->where(fn ($q) => $q->whereNull('produk_id')->orWhereIn('produk_id', $products->keys()))->exists()) {
            return false;
        }
        if ($order->cara_serah === 'antar' && ! DB::table('zona_antar')->where('id', $order->zona_antar_id)->where('aktif', true)->where('jarak_maks_km', '<=', 15)->exists()) {
            return false;
        }
        $custom = 0;
        $stock = [];
        $sale = [];
        foreach ($items as $item) {
            $p = $products->get($item->produk_id);
            $v = $variants->get($item->varian_produk_id);
            if (! $p || ! $p->aktif || ! DB::table('kategori')->where('id', $p->kategori_id)->where('aktif', true)->exists()
                || ($item->varian_produk_id && (! $v || ! $v->aktif || $v->produk_id != $p->id))) {
                return false;
            }
            if ($p->jenis === 'custom') {
                $min = $now->copy()->startOfDay()->addDays($p->lead_custom_hari + ($now->gt($now->copy()->setTime(15, 0)) ? 1 : 0));
                if ($order->tanggal_serah->lt($min)) {
                    return false;
                }
                $custom += $item->jumlah;
            } elseif ($p->jenis === 'jual' && $v) {
                $stock[$v->id] = ($stock[$v->id] ?? 0) + $item->jumlah;
                if ($stock[$v->id] > $v->stok) {
                    return false;
                }
                $sale[] = $item;
            } else {
                return false;
            }
        }
        if ($custom !== (int) $receipt->jumlah_custom) {
            return false;
        }
        if ($custom) {
            $capacity = DB::table('kapasitas_produksi_harian')->where('id', $receipt->kapasitas_id)->where('tanggal', $day)
                ->where('kelompok_kapasitas', 'buket_custom')->lockForUpdate()->first();
            if (! $capacity || $capacity->terpakai + $custom > min(15, $capacity->batas)) {
                return false;
            }
            DB::table('kapasitas_produksi_harian')->where('id', $capacity->id)->increment('terpakai', $custom);
        }
        foreach ($stock as $variant => $qty) {
            DB::table('varian_produk')->where('id', $variant)->decrement('stok', $qty);
        }
        foreach ($sale as $item) {
            DB::table('stok_ditahan')->insert(['item_pesanan_id' => $item->id, 'varian_produk_id' => $item->varian_produk_id, 'jumlah' => $item->jumlah]);
        }
        return true;
    }
}

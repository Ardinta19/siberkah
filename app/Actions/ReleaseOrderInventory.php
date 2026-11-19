<?php

namespace App\Actions;

use App\Models\Pesanan;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Caller holds the order lock and has checked that this is its first release. */
final class ReleaseOrderInventory
{
    public function release(Pesanan $order, bool $releaseCapacity): void
    {
        // Rental availability lives in hari_reservasi; only ready-sale items
        // decrement numeric stock. Never increment rental stock on release.
        $items = $order->items()->join('produk', 'produk.id', '=', 'item_pesanan.produk_id')
            ->leftJoin('stok_ditahan', 'stok_ditahan.item_pesanan_id', '=', 'item_pesanan.id')
            ->where(fn ($q) => $q->where('produk.jenis', 'jual')->orWhereNotNull('stok_ditahan.item_pesanan_id'))
            ->orderBy('item_pesanan.varian_produk_id')->orderBy('item_pesanan.id')
            ->get(['item_pesanan.id', 'item_pesanan.produk_id', 'item_pesanan.varian_produk_id', 'item_pesanan.jumlah',
                'stok_ditahan.varian_produk_id as held_variant', 'stok_ditahan.jumlah as held_quantity']);
        foreach ($items as $item) {
            if (! $item->held_variant || $item->held_variant != $item->varian_produk_id || $item->held_quantity != $item->jumlah || $item->held_quantity < 1) {
                throw new DomainException('Bukti pengurangan stok tidak cocok. Periksa stok sebelum melepas pesanan.');
            }
            $updated = DB::table('varian_produk')->where('id', $item->held_variant)
                ->where('produk_id', $item->produk_id)->increment('stok', $item->held_quantity);
            if (! $updated) {
                throw new DomainException('Varian stok tidak cocok dengan item pesanan.');
            }
            DB::table('stok_ditahan')->where('item_pesanan_id', $item->id)->delete();
        }

        $basket = DB::table('checkout_buket')->where('pesanan_id', $order->id)->first();
        if ($releaseCapacity && $basket && $basket->jumlah_custom > 0) {
            $released = DB::table('kapasitas_produksi_harian')->where('id', $basket->kapasitas_id)
                ->where('terpakai', '>=', $basket->jumlah_custom)->decrement('terpakai', $basket->jumlah_custom);
            if (! $released) {
                throw new DomainException('Bukti pemakaian kapasitas tidak cocok.');
            }
        }
        DB::table('hari_reservasi')->where('pesanan_id', $order->id)->delete();
    }
}

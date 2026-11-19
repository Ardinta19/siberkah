<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use DomainException;
use Illuminate\Support\Facades\DB;

/** T03 and T04. All other monetary transitions must lock the order first, too. */
final class ReleaseOrderHold
{
    public function expire(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            if ($order->status !== OrderStatus::MENUNGGU_PEMBAYARAN->value || ! $order->tahan_hingga_pada || $order->tahan_hingga_pada->isFuture()) {
                return false;
            }
            $this->release($order, true);
            return true;
        }, 3);
    }

    public function cancel(Pelanggan $customer, string $number): void
    {
        DB::transaction(function () use ($customer, $number) {
            $order = $customer->pesanan()->where('nomor', $number)->lockForUpdate()->firstOrFail();
            if ($order->status === OrderStatus::DIBATALKAN->value) {
                return;
            }
            if ($order->status !== OrderStatus::MENUNGGU_PEMBAYARAN->value) {
                throw new DomainException('Hanya pesanan menunggu pembayaran yang dapat dibatalkan langsung.');
            }
            $this->release($order, false);
        }, 3);
    }

    private function release(Pesanan $order, bool $expired): void
    {
        if ($order->jumlah_dibayar !== 0 || $order->pembayaran()->where('status', 'terverifikasi')->exists()) {
            throw new DomainException('Pembayaran sudah tercatat. Perlu rekonsiliasi sebelum unit dilepas.');
        }

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
        if ($basket && $basket->jumlah_custom > 0) {
            $released = DB::table('kapasitas_produksi_harian')->where('id', $basket->kapasitas_id)
                ->where('terpakai', '>=', $basket->jumlah_custom)->decrement('terpakai', $basket->jumlah_custom);
            if (! $released) {
                throw new DomainException('Bukti pemakaian kapasitas tidak cocok.');
            }
        }
        DB::table('hari_reservasi')->where('pesanan_id', $order->id)->delete();
        DB::table('pembayaran')->where('pesanan_id', $order->id)->where('status', 'menunggu')->update([
            'status' => 'kedaluwarsa', 'nominal_tertunda' => null, 'diubah_pada' => now(),
        ]);
        $before = $order->status;
        $order->status = $expired ? OrderStatus::KEDALUWARSA->value : OrderStatus::DIBATALKAN->value;
        if (! $expired) {
            $order->dibatalkan_pada = now();
        }
        $order->save();
        DB::table('riwayat_status_pesanan')->insert([
            'pesanan_id' => $order->id, 'status_lama' => $before, 'status_baru' => $order->status,
            'kode_transisi' => $expired ? 'T03' : 'T04', 'pelaku' => $expired ? 'sistem' : 'pelanggan', 'dibuat_pada' => now(),
        ]);
    }
}

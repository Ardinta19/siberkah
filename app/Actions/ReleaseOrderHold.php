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

        app(ReleaseOrderInventory::class)->release($order, true);
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

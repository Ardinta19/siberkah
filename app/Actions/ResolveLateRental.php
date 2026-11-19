<?php

namespace App\Actions;

use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

/** Decide one observed late transfer. A refund decision is not an outgoing transfer. */
final class ResolveLateRental
{
    public function handle(Pengguna $staff, int $id, int $receiptId): string
    {
        abort_unless($staff->aktif && $staff->canVerifyPayment(), 403);
        return DB::transaction(function () use ($staff, $id, $receiptId) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_bank')->where('pesanan_id', $id)->exists(), 404);
            $receipt = DB::table('mutasi_masuk')->where('id', $receiptId)->lockForUpdate()->first();
            abort_unless($receipt, 404);
            $invoice = DB::table('pembayaran')->where('id', $receipt->pembayaran_id)->where('pesanan_id', $id)->lockForUpdate()->first();
            abort_unless($invoice, 404);
            if ($receipt->alasan === 'reaktivasi_t13') {
                return 'dikonfirmasi';
            }
            if (in_array($receipt->alasan, ['unit_tidak_tersedia', 'buket_tidak_tersedia'], true)) {
                return $receipt->hasil === 'dikembalikan' ? 'dikembalikan' : 'perlu_pengembalian';
            }
            abort_unless($order->status === 'kedaluwarsa' && $receipt->hasil === 'perlu_rekonsiliasi' && $receipt->alasan === 'lewat_batas'
                && $invoice->status === 'kedaluwarsa' && $receipt->nominal == $invoice->nominal_diharapkan && $order->jumlah_dibayar === 0
                && $order->saldo === $order->total && $invoice->tagihan > 0 && (($invoice->jenis === 'penuh' && $invoice->tagihan == $order->total)
                    || ($invoice->jenis === 'dp' && $invoice->tagihan == intdiv($order->total, 2))), 409, 'Mutasi tidak cocok untuk reaktivasi.');
            $now = now('Asia/Jakarta');
            $basket = DB::table('checkout_buket')->where('pesanan_id', $id)->first();
            $available = ($invoice->jenis !== 'dp' || $order->batas_pelunasan_pada?->gt($now)) && ($basket
                ? app(RebookBouquet::class)->reserve($order, $basket, $now)
                : $this->reserveRental($order, $now));
            $receiptQuery = DB::table('mutasi_masuk')->where('id', $receiptId);
            if (! $available) {
                $receiptQuery->update(['alasan' => $basket ? 'buket_tidak_tersedia' : 'unit_tidak_tersedia']);
            } else {
                $remaining = $order->total - $invoice->tagihan;
                $order->update(['status' => 'dikonfirmasi', 'dikonfirmasi_pada' => $now, 'jumlah_dibayar' => $invoice->tagihan, 'saldo' => $remaining]);
                DB::table('pembayaran')->where('id', $invoice->id)->update(['status' => 'terverifikasi', 'nominal_diterima' => $receipt->nominal,
                    'diterima_pada' => $receipt->dicatat_pada, 'diverifikasi_pada' => $now, 'diubah_pada' => $now, 'pengguna_id' => $staff->id]);
                $receiptQuery->update(['hasil' => 'terverifikasi', 'alasan' => 'reaktivasi_t13']);
                if ($remaining) {
                    app(IssueRentalInvoice::class)->issue($id, 'pelunasan', $remaining, $now);
                }
            }
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $id, 'status_lama' => 'kedaluwarsa', 'status_baru' => $order->status,
                'kode_transisi' => $available ? 'T13' : 'T14', 'pengguna_id' => $staff->id, 'pelaku' => 'staf', 'dibuat_pada' => $now]);
            return $available ? 'dikonfirmasi' : 'perlu_pengembalian';
        }, 3);
    }
    private function reserveRental(Pesanan $order, \Illuminate\Support\Carbon $now): bool
    {
        $id = $order->id;
        $items = $order->items()->orderBy('id')->get();
        abort_unless($items->isNotEmpty() && $items->pluck('varian_produk_id')->unique()->count() === 1 && $items->every(fn ($i) => $i->unit_sewa_id && $i->jumlah == 1), 409);
        $first = $items->first();
        $product = DB::table('produk')->where('id', $first->produk_id)->lockForUpdate()->first();
        $variant = DB::table('varian_produk')->where('id', $first->varian_produk_id)->where('produk_id', $product->id)->lockForUpdate()->first();
        abort_unless($variant && $product->jenis === 'sewa', 409);
        $day = $order->tanggal_serah->toDateString();
        $blocked = DB::table('blokir_kalender')->where('tanggal', $day)->where(fn ($q) => $q->whereNull('produk_id')->orWhere('produk_id', $product->id))->exists();
        $units = DB::table('unit_sewa')->where('varian_produk_id', $variant->id)->where('status', 'aktif')->whereNotIn('id',
            DB::table('hari_reservasi')->select('unit_sewa_id')->where('tanggal', $day))->orderBy('id')->limit($items->count())->lockForUpdate()->pluck('id');
        $available = $day > $now->toDateString() && ! $blocked && $product->aktif && $variant->aktif && $units->count() === $items->count();
        if (! $available) {
            return false;
        }
        foreach ($items as $k => $item) {
            DB::table('item_pesanan')->where('id', $item->id)->update(['unit_sewa_id' => $units[$k], 'diubah_pada' => $now]);
            DB::table('hari_reservasi')->insert(['unit_sewa_id' => $units[$k], 'pesanan_id' => $id, 'item_pesanan_id' => $item->id, 'tanggal' => $day, 'dibuat_pada' => $now]);
        }
        return true;
    }
}

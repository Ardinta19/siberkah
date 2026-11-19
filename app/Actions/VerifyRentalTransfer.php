<?php

namespace App\Actions;

use App\Models\Pengguna;
use App\Models\Pesanan;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Manual bank-statement verification of rental invoices, never a customer confirmation. */
final class VerifyRentalTransfer
{
    public function record(Pengguna $staff, int $orderId, array $input): string
    {
        abort_unless($staff->aktif && $staff->canVerifyPayment(), 403);
        $data = Validator::make($input, [
            'pembayaran_id' => 'required|integer|min:1',
            'referensi' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/D'],
            'nominal' => 'required|integer|between:1,4294967295',
        ])->validate();
        $ref = strtoupper($data['referensi']);
        $amount = (int) $data['nominal'];

        return DB::transaction(function () use ($staff, $orderId, $data, $ref, $amount) {
            // Same lock order as expiry/cancellation. Check the deadline AFTER acquiring it.
            $order = Pesanan::lockForUpdate()->findOrFail($orderId);
            abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $orderId)->exists(), 404);
            $invoice = DB::table('pembayaran')->where('pesanan_id', $orderId)->where('id', $data['pembayaran_id'])
                ->whereIn('jenis', ['dp', 'penuh', 'pelunasan'])->lockForUpdate()->first();
            abort_unless($invoice, 404);
            $previous = DB::table('mutasi_masuk')->where('referensi', $ref)->first();
            if ($previous) {
                if ((int) $previous->pembayaran_id !== (int) $invoice->id || (int) $previous->nominal !== $amount) {
                    throw new DomainException('Referensi mutasi sudah tercatat dengan data berbeda.');
                }
                return $previous->hasil;
            }
            $now = now();
            $overdue = $order->tahan_hingga_pada && $order->tahan_hingga_pada->lte($now);
            if ($overdue && $order->status === 'menunggu_pembayaran') {
                app(ReleaseOrderHold::class)->expire($orderId);
                $order->refresh();
            }
            $balanceInvoice = $invoice->jenis === 'pelunasan';
            $permittedStatus = $balanceInvoice
                ? in_array($order->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap'], true)
                : $order->status === 'menunggu_pembayaran';
            $deadline = $balanceInvoice ? $order->batas_pelunasan_pada : $order->tahan_hingga_pada;
            $reason = match (true) {
                $order->status === 'kedaluwarsa' => 'lewat_batas',
                ! $permittedStatus, $invoice->status !== 'menunggu' => 'status_berubah',
                ! $deadline => 'batas_tidak_ada',
                $balanceInvoice && $deadline->lt($now) => 'pelunasan_terlambat',
                $amount !== (int) $invoice->nominal_diharapkan => 'nominal_berbeda',
                $order->jumlah_dibayar + $order->saldo !== $order->total,
                ! $balanceInvoice && $order->jumlah_dibayar !== 0,
                $balanceInvoice && $order->jumlah_dibayar <= 0,
                $invoice->jenis !== 'dp' && (int) $invoice->tagihan !== $order->saldo,
                $invoice->jenis === 'dp' && (int) $invoice->tagihan >= $order->saldo,
                $invoice->tagihan < 1, $invoice->tagihan > $order->saldo => 'saldo_tidak_sesuai',
                ! $this->holdsEveryUnit($order) => 'reservasi_tidak_utuh',
                default => null,
            };
            $result = $reason ? 'perlu_rekonsiliasi' : 'terverifikasi';
            try {
                DB::table('mutasi_masuk')->insert(['pembayaran_id' => $invoice->id, 'pengguna_id' => $staff->id,
                    'referensi' => $ref, 'nominal' => $amount, 'hasil' => $result, 'alasan' => $reason, 'dicatat_pada' => $now]);
            } catch (UniqueConstraintViolationException) {
                throw new DomainException('Referensi mutasi telah dipakai.');
            }
            if ($reason) {
                return $result;
            }
            DB::table('pembayaran')->where('id', $invoice->id)->update(['status' => 'terverifikasi', 'nominal_tertunda' => null,
                'nominal_diterima' => $amount, 'pengguna_id' => $staff->id, 'diterima_pada' => $now, 'diverifikasi_pada' => $now, 'diubah_pada' => $now]);
            // The matching code is an overpayment, not part of the rental principal.
            $remaining = $order->saldo - $invoice->tagihan;
            $balances = ['jumlah_dibayar' => $order->jumlah_dibayar + $invoice->tagihan, 'saldo' => $remaining];
            if ($balanceInvoice) {
                // Settlement is financial only: do not rewind production or mark as handed over.
                $order->update($balances);
                return $result;
            }
            $order->update($balances + ['status' => 'dikonfirmasi', 'dikonfirmasi_pada' => $now]);
            if ($invoice->jenis === 'dp') {
                app(IssueRentalInvoice::class)->issue($orderId, 'pelunasan', $remaining, $now);
            }
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $orderId, 'status_lama' => 'menunggu_pembayaran',
                'status_baru' => 'dikonfirmasi', 'kode_transisi' => 'T02', 'pelaku' => 'staf', 'pengguna_id' => $staff->id, 'dibuat_pada' => $now]);
            return $result;
        }, 3);
    }

    private function holdsEveryUnit(Pesanan $order): bool
    {
        $items = DB::table('item_pesanan')->where('pesanan_id', $order->id);
        return $items->exists() && ! $items->whereNotExists(fn ($days) => $days->selectRaw('1')->from('hari_reservasi')
            ->whereColumn('hari_reservasi.pesanan_id', 'item_pesanan.pesanan_id')
            ->whereColumn('hari_reservasi.item_pesanan_id', 'item_pesanan.id')
            ->whereColumn('hari_reservasi.unit_sewa_id', 'item_pesanan.unit_sewa_id')
            ->where('hari_reservasi.tanggal', $order->tanggal_serah->toDateString()))->exists();
    }
}

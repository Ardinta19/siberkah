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
            abort_unless(DB::table('checkout_bank')->where('pesanan_id', $orderId)->exists(), 404);
            $invoice = DB::table('pembayaran')->where('pesanan_id', $orderId)->where('id', $data['pembayaran_id'])
                ->where('tunai_saat_serah', false)->whereIn('jenis', ['dp', 'penuh', 'pelunasan'])->lockForUpdate()->first();
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
            $reason = $this->ineligible($order, $invoice, $now);
            if (! $reason && $amount !== (int) $invoice->nominal_diharapkan) {
                $reason = 'nominal_berbeda';
            }
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
            $this->credit($staff, $order, $invoice, $amount, $now, $now);
            return $result;
        }, 3);
    }

    public function decide(Pengguna $staff, int $id, int $mutation, string $decision, string $note): string
    {
        abort_unless($staff->aktif && $staff->canVerifyPayment(), 403);
        abort_unless(in_array($decision, ['terima', 'tolak'], true) && filled($note), 422);
        return DB::transaction(function () use ($staff, $id, $mutation, $decision, $note) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_bank')->where('pesanan_id', $id)->exists(), 404);
            $receipt = DB::table('mutasi_masuk')->where('id', $mutation)->lockForUpdate()->first();
            abort_unless($receipt, 404);
            $bill = DB::table('pembayaran')->where('pesanan_id', $id)->where('id', $receipt->pembayaran_id)->lockForUpdate()->first();
            abort_unless($bill, 404);
            $old = DB::table('keputusan_selisih')->where('mutasi_id', $mutation)->first();
            if ($old) {
                abort_unless($old->keputusan === $decision && $old->catatan === $note, 409, 'Keputusan sudah final.');
                return $decision;
            }
            abort_unless($receipt->hasil === 'perlu_rekonsiliasi' && $receipt->alasan === 'nominal_berbeda', 409, 'Mutasi ini bukan selisih yang belum diputuskan.');
            $now = now();
            if ($decision === 'terima') {
                // A note cannot waive the principal, create a customer credit, or override expiry.
                abort_unless($receipt->nominal >= $bill->tagihan && $receipt->nominal < $bill->nominal_diharapkan, 409,
                    'Hanya selisih kode unik yang dapat diterima. Pokok kurang atau dana berlebih perlu dikembalikan.');
                abort_if($this->ineligible($order, $bill, $now) !== null || ($order->status === 'menunggu_pembayaran' && $order->tahan_hingga_pada?->lte($now)),
                    409, 'Status, tenggat, saldo, atau reservasi tidak sesuai.');
                $this->credit($staff, $order, $bill, (int) $receipt->nominal, $now, $receipt->dicatat_pada);
            }
            DB::table('mutasi_masuk')->where('id', $mutation)->update(['hasil' => $decision === 'terima' ? 'terverifikasi' : 'perlu_rekonsiliasi',
                'alasan' => $decision === 'terima' ? 'selisih_diterima' : 'selisih_ditolak']);
            DB::table('keputusan_selisih')->insert(['mutasi_id' => $mutation, 'pengguna_id' => $staff->id, 'keputusan' => $decision,
                'catatan' => $note, 'diputuskan_pada' => $now]);
            return $decision;
        }, 3);
    }

    private function ineligible(Pesanan $order, object $invoice, $now): ?string
    {
        $balanceInvoice = $invoice->jenis === 'pelunasan';
        $closingBill = $balanceInvoice && DB::table('penutupan_sewa')->where('pesanan_id', $order->id)->where('tagihan_id', $invoice->id)->exists();
        $permittedStatus = $balanceInvoice
            ? ($closingBill ? $order->status === 'sudah_dikembalikan' : in_array($order->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap'], true))
            : $order->status === 'menunggu_pembayaran';
        $deadline = $balanceInvoice ? $order->batas_pelunasan_pada : $order->tahan_hingga_pada;
        return match (true) {
            $order->status === 'kedaluwarsa' => 'lewat_batas',
            ! $permittedStatus, $invoice->status !== 'menunggu' => 'status_berubah',
            ! $closingBill && ! $deadline => 'batas_tidak_ada',
            $balanceInvoice && ! $closingBill && $deadline->lte($now) => 'pelunasan_terlambat',
            $order->jumlah_dibayar + $order->saldo !== $order->total,
            ! $balanceInvoice && $order->jumlah_dibayar !== 0,
            $balanceInvoice && $order->jumlah_dibayar <= 0,
            $invoice->jenis !== 'dp' && (int) $invoice->tagihan !== $order->saldo,
            $invoice->jenis === 'dp' && (int) $invoice->tagihan >= $order->saldo,
            $invoice->tagihan < 1, $invoice->tagihan > $order->saldo => 'saldo_tidak_sesuai',
            ! $this->holdsEveryUnit($order) => 'reservasi_tidak_utuh',
            default => null,
        };
    }

    private function credit(Pengguna $staff, Pesanan $order, object $invoice, int $amount, $now, $received): void
    {
        DB::table('pembayaran')->where('id', $invoice->id)->update(['status' => 'terverifikasi', 'nominal_tertunda' => null,
            'nominal_diterima' => $amount, 'pengguna_id' => $staff->id, 'diterima_pada' => $received, 'diverifikasi_pada' => $now, 'diubah_pada' => $now]);
        // The matching code is an overpayment, not part of the rental principal.
        $remaining = $order->saldo - $invoice->tagihan;
        $balances = ['jumlah_dibayar' => $order->jumlah_dibayar + $invoice->tagihan, 'saldo' => $remaining];
        if ($invoice->jenis === 'pelunasan') {
            // Settlement is financial only: do not rewind production or mark as handed over.
            $order->update($balances);
            return;
        }
        $order->update($balances + ['status' => 'dikonfirmasi', 'dikonfirmasi_pada' => $now]);
        if ($invoice->jenis === 'dp' && ! $order->tunai_saat_serah) {
            app(IssueRentalInvoice::class)->issue($order->id, 'pelunasan', $remaining, $now);
        }
        DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $order->id, 'status_lama' => 'menunggu_pembayaran',
            'status_baru' => 'dikonfirmasi', 'kode_transisi' => 'T02', 'pelaku' => 'staf', 'pengguna_id' => $staff->id, 'dibuat_pada' => $now]);
    }

    private function holdsEveryUnit(Pesanan $order): bool
    {
        $items = DB::table('item_pesanan')->where('pesanan_id', $order->id);
        $basket = DB::table('checkout_buket')->where('pesanan_id', $order->id)->first();
        if ($basket) {
            $custom = 0;
            $lines = $items->leftJoin('stok_ditahan as h', 'h.item_pesanan_id', '=', 'item_pesanan.id')
                ->get(['item_pesanan.jumlah', 'item_pesanan.varian_produk_id', 'h.jumlah as ditahan', 'h.varian_produk_id as varian']);
            foreach ($lines as $line) {
                if ($line->ditahan === null) {
                    $custom += $line->jumlah;
                } elseif ($line->ditahan != $line->jumlah || $line->varian != $line->varian_produk_id) {
                    return false;
                }
            }
            return $lines->isNotEmpty() && $custom === (int) $basket->jumlah_custom && (! $custom || DB::table('kapasitas_produksi_harian')
                ->where('id', $basket->kapasitas_id)->where('terpakai', '>=', $custom)->exists());
        }
        return $items->exists() && ! $items->whereNotExists(fn ($days) => $days->selectRaw('1')->from('hari_reservasi')
            ->whereColumn('hari_reservasi.pesanan_id', 'item_pesanan.pesanan_id')
            ->whereColumn('hari_reservasi.item_pesanan_id', 'item_pesanan.id')
            ->whereColumn('hari_reservasi.unit_sewa_id', 'item_pesanan.unit_sewa_id')
            ->where('hari_reservasi.tanggal', $order->tanggal_serah->toDateString()))->exists();
    }
}

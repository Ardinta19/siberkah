<?php

namespace App\Actions;

use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

/** Apply confirmed physical cash once; a deposit is not a second incoming bank transfer. */
final class ApplyDamageDeposit
{
    public static function eligibleBill(Pesanan $order, ?int $billId)
    {
        return DB::table('pembayaran')->where('id',$billId)->where('pesanan_id',$order->id)
            ->where('jenis','pelunasan')->where('status','menunggu')->where('tagihan',$order->saldo)
            ->whereNull('dikonfirmasi_pelanggan_pada')->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('mutasi_masuk')->whereColumn('mutasi_masuk.pembayaran_id','pembayaran.id'));
    }

    public function handle(Pengguna $owner, int $id, int $amount, string $reason): int
    {
        abort_unless($owner->aktif && $owner->isOwner(), 403);
        return DB::transaction(function () use ($owner, $id, $amount, $reason) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $id)->exists(), 404);
            $old = DB::table('pemakaian_deposit')->where('pesanan_id', $id)->first();
            if ($old) {
                abort_unless($old->nominal == $amount && $old->alasan === $reason, 409, 'Potongan deposit sudah final.');
                return (int) $old->pembayaran_id;
            }
            $review = DB::table('penutupan_sewa')->where('pesanan_id', $id)->first();
            abort_unless($review && $review->setoran_pada && $order->jenis_jaminan === 'deposit' && $order->status === 'sudah_dikembalikan', 409,
                'Penilaian kerusakan dan setoran tunai harus sudah dikonfirmasi.');
            abort_unless($amount > 0 && $amount <= min($review->biaya_kerusakan, $order->nominal_jaminan, $order->saldo), 422,
                'Potongan tidak boleh melebihi biaya kerusakan, deposit atau saldo.');
            $bill = self::eligibleBill($order,$review->tagihan_id)->lockForUpdate()->first();
            abort_unless($bill && $order->jumlah_dibayar + $order->saldo === $order->total, 409, 'Tagihan sudah diproses. Jangan memotong deposit lagi.');
            $now = now();
            DB::table('pembayaran')->where('id', $bill->id)->update(['status' => 'kedaluwarsa', 'nominal_tertunda' => null, 'diubah_pada' => $now,
                'catatan_verifikasi' => 'Diganti setelah pemakaian deposit.']);
            $cash = DB::table('pembayaran')->insertGetId(['pesanan_id' => $id, 'jenis' => 'pelunasan', 'status' => 'terverifikasi', 'tagihan' => $amount,
                'nominal_diharapkan' => $amount, 'nominal_diterima' => $amount, 'pengguna_id' => $owner->id, 'diterima_pada' => $review->setoran_pada,
                'diverifikasi_pada' => $now, 'catatan_verifikasi' => 'Pelunasan kerusakan dari deposit', 'dibuat_pada' => $now, 'diubah_pada' => $now]);
            $order->update(['saldo' => $order->saldo - $amount, 'jumlah_dibayar' => $order->jumlah_dibayar + $amount]);
            $next = $order->saldo ? app(IssueRentalInvoice::class)->issue($id, 'pelunasan', $order->saldo, $now) : null;
            DB::table('penutupan_sewa')->where('pesanan_id', $id)->update(['tagihan_id' => $next]);
            DB::table('pemakaian_deposit')->insert(['pesanan_id' => $id, 'nominal' => $amount, 'alasan' => $reason, 'pengguna_id' => $owner->id,
                'pembayaran_id' => $cash, 'tagihan_lama_id' => $bill->id, 'dicatat_pada' => $now]);
            return $cash;
        }, 3);
    }
}

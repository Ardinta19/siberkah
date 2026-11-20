<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CustomerPaymentController extends Controller
{
    private function ownedOrder(Request $request, string $nomor)
    {
        return $request->user('customer')->pesanan()->where('nomor', $nomor)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('checkout_bank')->whereColumn('pesanan_id', 'pesanan.id'));
    }

    public function show(Request $request, string $nomor)
    {
        $order = $this->ownedOrder($request, $nomor)->firstOrFail();
        $invoices = DB::table('pembayaran')->where('pesanan_id', $order->id)->orderBy('id')->get(['id','jenis','tunai_saat_serah','status','tagihan','kode_unik','nominal_diharapkan','dikonfirmasi_pelanggan_pada','diverifikasi_pada']);
        $decisions = DB::table('keputusan_selisih as k')->join('mutasi_masuk as m', 'm.id', '=', 'k.mutasi_id')
            ->join('pembayaran as p', 'p.id', '=', 'm.pembayaran_id')->where('p.pesanan_id', $order->id)->orderBy('k.mutasi_id')
            ->get(['m.pembayaran_id', 'k.keputusan', 'k.catatan', 'k.diputuskan_pada']);
        return response()->json(['nomor' => $nomor, 'status' => $order->status, 'saldo' => $order->saldo, 'tunai_saat_serah' => $order->tunai_saat_serah, 'cara_serah' => $order->cara_serah, 'tahan_hingga_pada' => $order->tahan_hingga_pada,
            'batas_pelunasan_pada' => $order->batas_pelunasan_pada, 'pembayaran' => $invoices, 'keputusan_selisih' => $decisions])->header('Cache-Control', 'private, no-store');
    }

    public function confirm(Request $request, string $nomor)
    {
        $id = $request->validate(['pembayaran_id' => 'required|integer|min:1'])['pembayaran_id'];
        DB::transaction(function () use ($request, $nomor, $id) {
            $order = $this->ownedOrder($request, $nomor)->lockForUpdate()->firstOrFail();
            $query = DB::table('pembayaran')->where('pesanan_id', $order->id)->where('id', $id)->where('tunai_saat_serah', false)->whereIn('jenis', ['dp','penuh','pelunasan']);
            $bill = $query->lockForUpdate()->first();
            abort_unless($bill, 404);
            abort_unless($bill->status === 'menunggu', 409, 'Tagihan sudah ditutup.');
            if (! $bill->dikonfirmasi_pelanggan_pada) {
                $query->update(['dikonfirmasi_pelanggan_pada' => now(), 'diubah_pada' => now()]);
            }
        }, 3);
        return response()->json(['hasil' => 'menunggu_pemeriksaan'], 202);
    }
}

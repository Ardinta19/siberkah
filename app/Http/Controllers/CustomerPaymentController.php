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
        $invoices = DB::table('pembayaran')->where('pesanan_id', $order->id)->orderBy('id')->get(['id','jenis','status','tagihan','kode_unik','nominal_diharapkan','dikonfirmasi_pelanggan_pada','diverifikasi_pada']);
        return response()->json(['nomor' => $nomor, 'status' => $order->status, 'saldo' => $order->saldo, 'tahan_hingga_pada' => $order->tahan_hingga_pada,
            'batas_pelunasan_pada' => $order->batas_pelunasan_pada, 'pembayaran' => $invoices])->header('Cache-Control', 'private, no-store');
    }

    public function confirm(Request $request, string $nomor)
    {
        $id = $request->validate(['pembayaran_id' => 'required|integer|min:1'])['pembayaran_id'];
        DB::transaction(function () use ($request, $nomor, $id) {
            $order = $this->ownedOrder($request, $nomor)->lockForUpdate()->firstOrFail();
            $query = DB::table('pembayaran')->where('pesanan_id', $order->id)->where('id', $id)->whereIn('jenis', ['dp','penuh','pelunasan']);
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

<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Verified principal received, less verified refunds. Not a bank statement or profit calculation. */
final class VerifiedRevenueController extends Controller
{
    private function payments(Request $request)
    {
        $dates = $request->validate(['mulai' => 'required|date_format:Y-m-d', 'akhir' => 'required|date_format:Y-m-d|after_or_equal:mulai']);
        $start = CarbonImmutable::parse($dates['mulai'], 'Asia/Jakarta')->startOfDay();
        $end = CarbonImmutable::parse($dates['akhir'], 'Asia/Jakarta')->addDay()->startOfDay();
        abort_if($end->gt($start->addYear()), 422, 'Rentang maksimal satu tahun.');
        // One row per order, never multiply a payment by its item count.
        $category = DB::table('item_pesanan as i')->join('produk as p', 'p.id', '=', 'i.produk_id')
            ->join('kategori as k', 'k.id', '=', 'p.kategori_id')->groupBy('i.pesanan_id')
            ->selectRaw("i.pesanan_id, CASE WHEN COUNT(DISTINCT k.id) = 1 THEN MIN(k.nama) ELSE 'Lintas kategori' END AS kategori");
        return DB::table('pembayaran as b')->leftJoinSub($category, 'c', 'c.pesanan_id', '=', 'b.pesanan_id')
            ->leftJoin('penjualan_toko as t', 't.pesanan_id', '=', 'b.pesanan_id')
            ->where('b.status', 'terverifikasi')->whereIn('b.jenis', ['dp','penuh','pelunasan','refund'])
            ->where('b.diverifikasi_pada', '>=', $start)->where('b.diverifikasi_pada', '<', $end)
            ->selectRaw("b.id, b.pesanan_id, DATE(b.diverifikasi_pada) AS tanggal, COALESCE(c.kategori, 'Tanpa kategori') AS kategori,
                CASE WHEN t.pesanan_id IS NULL THEN 'bank' ELSE 'tunai' END AS kanal,
                CASE WHEN b.jenis = 'refund' THEN 0 ELSE b.tagihan END AS pokok,
                CASE WHEN b.jenis = 'refund' THEN b.nominal_diterima ELSE 0 END AS refund");
    }

    public function index(Request $request)
    {
        $rows = DB::query()->fromSub($this->payments($request), 'r')->groupBy('kategori', 'kanal')->orderBy('kategori')->orderBy('kanal')
            ->selectRaw('kategori, kanal, COUNT(DISTINCT pesanan_id) AS pesanan, SUM(pokok) AS pokok, SUM(refund) AS refund')->get()
            ->map(fn ($r) => ['kategori' => $r->kategori, 'kanal' => $r->kanal, 'pesanan' => (int) $r->pesanan, 'pokok' => (int) $r->pokok,
                'refund' => (int) $r->refund, 'neto' => (int) $r->pokok - (int) $r->refund]);
        return response()->json(['mulai' => $request->mulai, 'akhir' => $request->akhir, 'neto' => $rows->sum('neto'), 'rincian' => $rows])
            ->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request)
    {
        $rows = $this->payments($request)->orderBy('b.id');
        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'wb');
            $key = random_bytes(32);
            fputcsv($file, ['tanggal','kode_pesanan','kanal','pokok','refund','neto'], ',', '"', '');
            foreach ($rows->cursor() as $r) {
                $code = substr(hash_hmac('sha256', (string) $r->pesanan_id, $key), 0, 24);
                fputcsv($file, [$r->tanggal, $code, $r->kanal, $r->pokok, $r->refund, (int) $r->pokok - (int) $r->refund], ',', '"', '');
            }
            fclose($file);
        }, 'penerimaan-terverifikasi.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}

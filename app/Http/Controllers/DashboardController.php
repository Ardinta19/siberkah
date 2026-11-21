<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    public function hariIni(Request $request)
    {
        $now = now('Asia/Jakarta');
        $day = $now->toDateString();
        $today = DB::table('pesanan')->where('tanggal_serah', $day);
        $summary = [
            'verifikasi' => DB::table('pembayaran')->where('status', 'menunggu')->whereNotNull('dikonfirmasi_pelanggan_pada')->distinct()->count('pesanan_id'),
            'antar' => (clone $today)->where('status', 'siap')->where('cara_serah', 'antar')->count(),
            'jemput' => (clone $today)->where('status', 'sudah_diserahkan')->whereIn('id', DB::table('checkout_sewa')->select('pesanan_id'))->count(),
            'produksi' => (clone $today)->where('status', 'sedang_diproduksi')->count(),
        ];
        // Only warn while the unit is actually in custody. An approved extension moves its deadline.
        $alerts = DB::table('penguasaan_unit as c')->join('pesanan as p', 'p.id', '=', 'c.pesanan_id')->join('unit_sewa as u', 'u.id', '=', 'c.unit_sewa_id')
            ->join('hari_reservasi as h', fn ($j) => $j->on('h.unit_sewa_id', '=', 'c.unit_sewa_id')->whereColumn('h.pesanan_id', '!=', 'p.id'))
            ->join('pesanan as n', 'n.id', '=', 'h.pesanan_id')->whereIn('n.status', ['menunggu_pembayaran','dikonfirmasi','sedang_diproduksi','siap'])
            ->whereBetween('h.tanggal', [$day, $now->copy()->addDay()->toDateString()])->where('p.status', 'sudah_diserahkan')
            ->whereRaw("COALESCE((SELECT MAX(x.batas_baru) FROM perpanjangan_sewa x WHERE x.pesanan_id=p.id), TIMESTAMP(p.tanggal_serah,'18:00:00')) < ?", [$now])
            ->orderBy('h.tanggal')->orderBy('u.kode')->get(['u.kode','p.nomor as pesanan_sekarang','n.nomor as pesanan_berikut','h.tanggal','n.jam_serah_mulai']);
        $data = ['tanggal' => $day, 'ringkasan' => $summary, 'peringatan' => $alerts];
        return ($request->expectsJson() ? response()->json($data) : response()->view('admin.dashboard.index', $data))->header('Cache-Control', 'private, no-store');
    }
}

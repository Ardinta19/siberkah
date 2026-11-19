<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Admin Report Controller - Reports and exports
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 * K-08: Admin Rekap - periode harian/mingguan/bulanan, rincian per kategori, hari tersibuk, unduh CSV anonim
 */
class AdminReportController extends BaseController
{
    /**
     * Display the reports index.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $query = Pembayaran::whereNotNull('diterima_pana');

        // Filter by period
        if ($request->has('period')) {
            $period = $request->period;
            switch ($period) {
                case 'harian':
                    $query->whereDate('diterima_pana', now()->toDateString());
                    break;
                case 'mingguan':
                    $query->whereBetween('diterima_pana', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString(),
                    ]);
                    break;
                case 'bulanan':
                    $query->whereMonth('diterima_pana', now()->month)
                        ->whereYear('diterima_pana', now()->year);
                    break;
            }
        }

        $pembayaran = $query->latest()->paginate(20);

        // Calculate summary stats
        $totalPembayaran = Pembayaran::whereNotNull('diterima_pana')->sum('tagihan');
        $totalTransaksi = Pembayaran::whereNotNull('diterima_pana')->count();

        return view('admin.reports.index', [
            'pembayaran' => $pembayaran,
            'totalPembayaran' => $totalPembayaran,
            'totalTransaksi' => $totalTransaksi,
            'period' => $request->get('period'),
        ]);
    }

    /**
     * Export anonymous CSV data for research.
     *
     * @return File|void
     */
    public function exportAnonymousCsv()
    {
        // Generate CSV with anonymized data
        // - Hapus nama, WA, alamat, nomor pesanan → kode acak
        // - Sisa: tanggal, kategori, total, status
        // - Simpan ke storage/app/export/anonymous-research-data-{tanggal}.csv

        $filename = 'anonymous-research-data-'.now()->format('Ymd').'.csv';
        $path = storage_path("app/export/{$filename}");

        // Ensure export directory exists
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($path, 'w');
        if ($handle === false) {
            return back()->with('error', 'Gagal menghasilkan file CSV.');
        }

        // Write header
        fputcsv($handle, [
            'tanggal',
            'kategori',
            'total_pesanan',
            'total_tagihan',
            'status',
        ]);

        // Query anonymized data
        $data = Pesanan::selectRaw('DATE(dibuat_pana) as tanggal, kategori.nama as kategori, COUNT(*) as total_pesanan')
            ->leftJoin('item_pesanan', 'pesanan.id', 'item_pesanan.pesanan_id')
            ->leftJoin('produk', 'item_pesanan.produk_id', 'produk.id')
            ->leftJoin('kategori', 'produk.kategori_id', 'kategori.id')
            ->groupBy('tanggal', 'kategori.nama')
            ->latest()
            ->get();

        foreach ($data as $row) {
            fputcsv($handle, [
                $row->tanggal,
                $row->kategori,
                $row->total_pesanan,
                0, // Will be calculated from payments
                'anonymized',
            ]);
        }

        fclose($handle);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
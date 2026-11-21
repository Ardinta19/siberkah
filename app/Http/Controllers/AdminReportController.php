<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bank cash flow, not accrual revenue. Deposits held by couriers are not sales. */
final class AdminReportController extends Controller
{
    private function totals(Request $request): array
    {
        $period = $request->validate(['period' => 'sometimes|in:harian,mingguan,bulanan'])['period'] ?? 'bulanan';
        $start = now('Asia/Jakarta')->startOfDay();
        $end = match ($period) {
            'harian' => $start->copy()->addDay(),
            'mingguan' => $start->startOfWeek()->copy()->addWeek(),
            'bulanan' => $start->startOfMonth()->copy()->addMonth(),
        };
        $streams = [
            ['mutasi_masuk', 'dicatat_pada', 'nominal', 'masuk'],
            ['pengembalian_mutasi', 'dicatat_pada', 'nominal', 'pengembalian_mutasi'],
            ['pembayaran', 'diverifikasi_pada', 'nominal_diterima', 'refund_pembatalan'],
        ];
        $days = [];
        foreach ($streams as [$table, $time, $amount, $key]) {
            $q = DB::table($table)->where($time, '>=', $start)->where($time, '<', $end);
            if ($table === 'pembayaran') {
                $q->where('jenis', 'refund')->where('status', 'terverifikasi');
            }
            foreach ($q->selectRaw("DATE($time) AS hari, SUM($amount) AS jumlah")->groupByRaw("DATE($time)")->get() as $row) {
                $days[$row->hari] ??= ['tanggal' => $row->hari, 'masuk' => 0, 'pengembalian_mutasi' => 0, 'refund_pembatalan' => 0];
                $days[$row->hari][$key] = (int) $row->jumlah;
            }
        }
        ksort($days);
        foreach ($days as &$day) {
            $day['neto'] = $day['masuk'] - $day['pengembalian_mutasi'] - $day['refund_pembatalan'];
        }
        unset($day);
        return ['periode' => $period, 'mulai' => $start->toDateString(), 'akhir_eksklusif' => $end->toDateString(),
            'neto' => array_sum(array_column($days, 'neto')), 'harian' => array_values($days)];
    }

    public function index(Request $request)
    {
        $report = $this->totals($request);
        return ($request->getPreferredFormat() === 'html' && !$request->acceptsAnyContentType() && !$request->expectsJson()
            ? response()->view('owner.laporan.index',$report) : response()->json($report))->header('Cache-Control', 'private, no-store');
    }

    public function exportAnonymousCsv(Request $request)
    {
        $report = $this->totals($request);
        return response()->streamDownload(function () use ($report) {
            $file = fopen('php://output', 'wb');
            fputcsv($file, ['tanggal','masuk','pengembalian_mutasi','refund_pembatalan','neto'], ',', '"', '');
            foreach ($report['harian'] as $day) {
                fputcsv($file, array_values($day), ',', '"', '');
            }
            fclose($file);
        }, 'arus-kas-'.$report['mulai'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}

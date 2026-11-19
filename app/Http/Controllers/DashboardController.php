<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Dashboard Controller - Owner/Admin dashboard
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 */
class DashboardController extends BaseController
{
    /**
     * Show the dashboard with today's activities.
     * S-01 Agenda Harian - cards per tugas (antar/jemput/produksi/verifikasi)
     *
     * @return View
     */
    public function hariIni()
    {
        // TODO: Implement actual dashboard data
        // - Antrian pengantaran/jemput hari ini
        // - Hitung jumlah order menunggu verifikasi
        // - Cek unit yang belum kembali
        // - Ambil data dari log_aktivitas hari ini

        $stats = [
            'order_menunggu' => 0,
            'order_produksi' => 0,
            'unit_kembali' => 0,
        ];

        return view('pages.owner.dashboard.hari-ini', [
            'stats' => $stats,
            'title' => 'Dashboard - Berkah Kreatif',
        ]);
    }
}

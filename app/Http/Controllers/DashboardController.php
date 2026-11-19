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
        return view('owner.index');
    }
}

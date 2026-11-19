<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Admin Activity Log Controller - Read-only log viewing
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 * K-08: Admin Aktivitas - melihat log aktivitas sistem
 */
class AdminActivityLogController extends BaseController
{
    /**
     * Display the activity log index.
     *
     * @return View
     */
    public function index()
    {
        return response()->json(LogAktivitas::orderByDesc('id')->paginate(25,
            ['id','pengguna_id','pelanggan_id','aksi','subjek_tabel','subjek_id','sebelum','sesudah','dibuat_pada']))
            ->header('Cache-Control', 'private, no-store');
    }
}

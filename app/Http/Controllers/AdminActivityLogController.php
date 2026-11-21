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
    public function index(Request $request)
    {
        $rates = $request->query('kelompok') === 'tarif';
        $rows = LogAktivitas::orderByDesc('id')->when($rates,fn ($q) => $q->whereIn('aksi',['ubah_tarif_zona','ubah_tarif_kerusakan']))->paginate(25,
            ['id','pengguna_id','pelanggan_id','aksi','subjek_tabel','subjek_id','sebelum','sesudah','dibuat_pada'])->appends($rates ? ['kelompok'=>'tarif'] : []);
        return ($request->getPreferredFormat() === 'html' && !$request->acceptsAnyContentType() && !$request->expectsJson()
            ? response()->view('owner.aktivitas',compact('rows','rates')) : response()->json($rows))
            ->header('Cache-Control', 'private, no-store');
    }
}

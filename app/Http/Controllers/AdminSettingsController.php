<?php

namespace App\Http\Controllers;

use App\Models\BlokirKalender;
use App\Models\KapasitasProduksiHarian;
use App\Models\Pengaturan;
use App\Models\Pesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Admin Settings Controller - Business settings management
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 * K-08: Admin Pengaturan - form semua nilai bisnis (jam batas, ambang DP, penahanan, denda, zona, tarif kerusakan, retensi), log aktivitas read-only
 */
class AdminSettingsController extends BaseController
{
    /**
     * Display the settings index.
     *
     * @return View
     */
    public function index()
    {
        $pengaturan = Pengaturan::orderBy('kunci')->get();

        $pengaturanMap = [];
        foreach ($pengaturan as $setting) {
            $pengaturanMap[$setting->kunci] = $setting->nilai;
        }

        return view('admin.settings.index', [
            'pengaturan' => $pengaturanMap,
        ]);
    }

    /**
     * Update a setting value.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'kunci' => 'required|string|max:60',
            'nilai' => 'required|string|max:500',
            'tipe' => 'sometimes|string|max:15',
        ]);

        $setting = Pengaturan::updateOrCreate(
            ['kunci' => $request->kunci],
            [
                'nilai' => $request->nilai,
                'tipe' => $request->tipe ?? 'teks',
            ]
        );

        return back()
            ->with('success', 'Pengaturan "'.$request->kunci.'" berhasil diupdate.');
    }

    /**
     * Manage calendar blocks.
     *
     * @return RedirectResponse
     */
    public function manageCalendarBlocks(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'produk_id' => 'sometimes|exists:produk,id',
            'alasan' => 'required|string|max:160',
        ]);

        // Cek bentrok dengan pesanan aktif
        $bentrok = Pesanan::where('tanggal_serah', $request->tanggal)
            ->where('status', '!=', 'dibatalkan')
            ->exists();

        if ($bentrok && ! $request->has('produk_id')) {
            return back()
                ->with('error', 'Tanggal ini sudah memiliki pesanan aktif. Hanya bisa blokir untuk produk tertentu.');
        }

        BlokirKalender::create([
            'tanggal' => $request->tanggal,
            'produk_id' => $request->produk_id,
            'alasan' => $request->alasan,
            'pengguna_id' => auth('staff')->id(),
        ]);

        return back()
            ->with('success', 'Kalender berhasil diblokir.');
    }

    /**
     * Manage production capacity.
     *
     * @return RedirectResponse
     */
    public function manageProductionCapacity(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
            'kelompok_kapasitas' => 'required|string|max:30',
            'batas' => 'required|integer|between:0,65535',
        ]);

        KapasitasProduksiHarian::updateOrCreate(
            ['tanggal' => $request->tanggal, 'kelompok_kapasitas' => $request->kelompok_kapasitas],
            [
                'batas' => $request->batas,
            ]
        );

        return back()
            ->with('success', 'Kapasitas produksi harian berhasil diupdate.');
    }
}
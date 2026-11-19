<?php

namespace App\Http\Controllers;

use App\Models\BlokirKalender;
use App\Models\KapasitasProduksiHarian;
use App\Support\BusinessSettings;
use Illuminate\Support\Facades\DB;
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
        $settings = [];
        foreach (BusinessSettings::RULES as $key => [$default, $min, $max]) {
            $settings[$key] = ['nilai' => BusinessSettings::value($key), 'bawaan' => $default, 'min' => $min, 'maks' => $max];
        }
        return response()->json(['pengaturan' => $settings])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $key = $request->validate(['kunci' => ['required', \Illuminate\Validation\Rule::in(array_keys(BusinessSettings::RULES))]])['kunci'];
        [$default, $min, $max] = BusinessSettings::RULES[$key];
        $number = (int) $request->validate(['nilai' => "required|integer|between:$min,$max"])['nilai'];
        DB::transaction(function () use ($key, $number, $default, $request) {
            DB::table('pengaturan')->insertOrIgnore(['kunci' => $key, 'tipe' => 'integer', 'nilai' => (string) $default, 'dibuat_pada' => now()]);
            $row = DB::table('pengaturan')->where('kunci', $key)->lockForUpdate()->first();
            if ((string) $row->nilai === (string) $number) {
                return;
            }
            DB::table('pengaturan')->where('id', $row->id)->update(['tipe' => 'integer', 'nilai' => (string) $number, 'diubah_pada' => now()]);
            DB::table('log_aktivitas')->insert(['aksi' => 'ubah_pengaturan', 'pengguna_id' => $request->user('staff')->id,
                'subjek_tabel' => 'pengaturan', 'subjek_id' => $row->id, 'dibuat_pada' => now(),
                'sebelum' => json_encode([$key => $row->nilai], JSON_THROW_ON_ERROR), 'sesudah' => json_encode([$key => $number], JSON_THROW_ON_ERROR)]);
        }, 3);

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

        $key = $request->only('tanggal', 'kelompok_kapasitas');
        $limit = (int) $request->batas;
        abort_if($key['kelompok_kapasitas'] === 'buket_custom' && $limit > 15, 422);
        \Illuminate\Support\Facades\DB::transaction(function () use ($key, $limit) {
            $table = \Illuminate\Support\Facades\DB::table('kapasitas_produksi_harian');
            $table->insertOrIgnore($key + ['batas' => $limit, 'dibuat_pada' => now()]);
            $current = $table->where($key)->lockForUpdate()->first();
            abort_if($current->terpakai > $limit, 409, 'Kapasitas baru di bawah jumlah yang sudah dipesan.');
            $table->update(['batas' => $limit, 'diubah_pada' => now()]);
        }, 3);

        return back()
            ->with('success', 'Kapasitas produksi harian berhasil diupdate.');
    }
}
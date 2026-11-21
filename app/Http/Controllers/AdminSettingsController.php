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
    public function index(Request $request)
    {
        $settings = [];
        foreach (BusinessSettings::RULES as $key => [$default, $min, $max]) {
            $settings[$key] = ['nilai' => BusinessSettings::value($key), 'bawaan' => $default, 'min' => $min, 'maks' => $max];
        }
        $data = ['pengaturan' => $settings, 'tarif_kerusakan' => DB::table('tarif_kerusakan')->orderBy('kode')->get(['id','kode','nama','satuan','tarif']), 'zona' => DB::table('zona_antar')->orderBy('jarak_maks_km')->get(['id','nama','jarak_maks_km','aktif','ongkir'])];
        return ($request->getPreferredFormat() === 'html' && !$request->acceptsAnyContentType() && !$request->expectsJson()
            ? response()->view('owner.pengaturan.index',$data) : response()->json($data))->header('Cache-Control', 'private, no-store');
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
            if ($request->has('sebelum') && (string)$row->nilai !== (string)$request->input('sebelum')) {
                throw \Illuminate\Validation\ValidationException::withMessages(['nilai'=>'Nilai telah berubah. Periksa nilai terbaru sebelum menyimpan kembali.']);
            }
            DB::table('pengaturan')->where('id', $row->id)->update(['tipe' => 'integer', 'nilai' => (string) $number, 'diubah_pada' => now()]);
            DB::table('log_aktivitas')->insert(['aksi' => 'ubah_pengaturan', 'pengguna_id' => $request->user('staff')->id,
                'subjek_tabel' => 'pengaturan', 'subjek_id' => $row->id, 'dibuat_pada' => now(),
                'sebelum' => json_encode([$key => $row->nilai], JSON_THROW_ON_ERROR), 'sesudah' => json_encode([$key => $number], JSON_THROW_ON_ERROR)]);
        }, 3);

        return back()
            ->with('success', 'Pengaturan "'.$request->kunci.'" berhasil diupdate.');
    }

    public function zoneRate(Request $request, int $id)
    {
        return $this->updateRate($request, $id, 'zona_antar', 'ongkir', 'ubah_tarif_zona');
    }

    public function damageRate(Request $request, int $id)
    {
        return $this->updateRate($request, $id, 'tarif_kerusakan', 'tarif', 'ubah_tarif_kerusakan');
    }

    private function updateRate(Request $request, int $id, string $table, string $field, string $action)
    {
        $browser = $request->input('asal') === 'pengaturan' && !$request->expectsJson();
        $anchor = "$field-$id";
        $url = route('admin.pengaturan.index').'#'.$anchor;
        $validator = validator($request->all(), ['sebelum' => 'required|integer|min:0', $field => 'required|integer|between:0,10000000']);
        if ($browser && $validator->fails()) {
            return redirect($url)->with('rate_warning',[$anchor=>'Masukkan nominal rupiah bulat antara 0 dan 10.000.000; muat ulang bila nilai acuan tidak tersedia.']);
        }
        $data = $validator->validate();
        $saved = DB::transaction(function () use ($data, $id, $table, $field, $action) {
            $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            $fee = (int) $data[$field];
            if ((int) $row->$field === $fee) { return true; }
            if ((int) $data['sebelum'] !== (int) $row->$field) { return false; }
            DB::table($table)->where('id', $id)->update([$field => $fee, 'diubah_pada' => now()]);
            \App\Support\StaffAudit::record($action,$table,$id,[$field=>(int)$row->$field],[$field=>$fee]);
            return true;
        }, 3);
        if (!$saved) {
            abort_unless($browser,409,'Tarif sudah berubah, muat ulang.');
            return redirect($url)->with('rate_warning',[$anchor=>'Tarif sudah berubah. Periksa nilai terbaru sebelum menyimpan kembali.']);
        }
        return $browser ? redirect($url)->with('rate_saved',$anchor) : response()->noContent();
    }

    /**
     * Manage calendar blocks.
     *
     * @return RedirectResponse
     */
    public function calendar(Request $request)
    {
        $day = $request->validate(['tanggal'=>'sometimes|required|date_format:Y-m-d'])['tanggal'] ?? now('Asia/Jakarta')->toDateString();
        $capacity = DB::table('kapasitas_produksi_harian')->where(['tanggal'=>$day,'kelompok_kapasitas'=>'buket_custom'])->first();
        $blocks = BlokirKalender::with('produk:id,nama')->where('tanggal',$day)->orderBy('id')->get();
        $products = DB::table('produk')->where('aktif',true)->orderBy('nama')->get(['id','nama']);
        $operational = $request->routeIs('admin.kalender');
        $code = $operational ? ($request->validate(['unit'=>'nullable|string|max:100'])['unit'] ?? '') : '';
        $unit = $code !== '' ? DB::table('unit_sewa')->where('kode',$code)->first(['id','kode','status']) : null;
        $unitBlock = $unit ? DB::table('blokir_unit_harian')->where(['tanggal'=>$day,'unit_sewa_id'=>$unit->id])->first() : null;
        $reserved = $unit ? DB::table('hari_reservasi as h')->join('pesanan as p','p.id','=','h.pesanan_id')
            ->where(['h.tanggal'=>$day,'h.unit_sewa_id'=>$unit->id])->first(['p.id','p.nomor']) : null;
        return response()->view('owner.pengaturan.kalender',compact('day','capacity','blocks','products','operational','code','unit','unitBlock','reserved'))->header('Cache-Control','private, no-store');
    }

    public function releaseCalendarBlock(Request $request, int $id)
    {
        $data = $request->validate(['tanggal'=>'required|date_format:Y-m-d|after_or_equal:today',
            'konfirmasi'=>'accepted','alasan_buka'=>'required|string|max:160']);
        $removed = DB::transaction(function () use ($id, $data) {
            $block = DB::table('blokir_kalender')->where('tanggal',$data['tanggal'])->where('id',$id)->lockForUpdate()->first();
            if (!$block) { return false; }
            DB::table('blokir_kalender')->where('id',$id)->delete();
            \App\Support\StaffAudit::record('buka_blokir_kalender','blokir_kalender',$id,
                ['tanggal'=>$block->tanggal,'produk_id'=>$block->produk_id,'alasan'=>$block->alasan],
                ['aktif'=>false,'alasan_buka'=>$data['alasan_buka']]);
            return true;
        },3);
        return redirect()->route('admin.pengaturan.kalender',['tanggal'=>$data['tanggal']])->with('success',
            $removed ? 'Blokir terpilih telah dibuka. Blokir lain dan kapasitas tanggal ini tetap berlaku.' : 'Blokir tidak ditemukan pada tanggal ini. Tidak ada perubahan.');
    }

    public function manageCalendarBlocks(Request $request)
    {
        if ($request->input('asal') === 'kalender') {
            $request->validate(['tanggal'=>'required|date_format:Y-m-d|after_or_equal:today', 'cakupan'=>'required|in:toko,produk',
                'produk_id'=>'required_if:cakupan,produk|prohibited_if:cakupan,toko|nullable|exists:produk,id','konfirmasi'=>'accepted']);
        }
        $request->validate([
            'tanggal' => 'required|date',
            'produk_id' => 'nullable|exists:produk,id',
            'alasan' => 'required|string|max:160',
        ]);

        $bentrok = Pesanan::whereDate('tanggal_serah', $request->tanggal)
            ->whereIn('status', ['menunggu_pembayaran','dikonfirmasi','sedang_diproduksi','siap','sudah_diserahkan'])
            ->when($request->filled('produk_id'), fn ($q) => $q->whereHas('items', fn ($i) => $i->where('produk_id', $request->produk_id)))
            ->pluck('nomor');

        DB::transaction(function () use ($request) {
            $block = BlokirKalender::create([
            'tanggal' => $request->tanggal,
            'produk_id' => $request->produk_id,
            'alasan' => $request->alasan,
            'pengguna_id' => auth('staff')->id(),
            ]);
            \App\Support\StaffAudit::record('blokir_kalender', 'blokir_kalender', $block->id, null, $request->only('tanggal','produk_id','alasan'));
        });

        if ($request->expectsJson()) {
            return response()->json(['peringatan_pesanan_aktif' => $bentrok], 201);
        }
        $back = $request->input('asal') === 'kalender' ? redirect()->route($request->user('staff')->isOwner() && !$request->boolean('operasional') ? 'admin.pengaturan.kalender' : 'admin.kalender',['tanggal'=>$request->tanggal]) : back();
        return $back->with('success', 'Kalender berhasil diblokir.')->with('peringatan_pesanan_aktif', $bentrok->all());
    }

    /**
     * Manage production capacity.
     *
     * @return RedirectResponse
     */
    public function manageProductionCapacity(Request $request)
    {
        $editor = $request->input('asal') === 'kalender';
        if ($editor) {
            $request->validate(['tanggal'=>'required|date_format:Y-m-d|after_or_equal:today','sebelum'=>'required|integer|between:0,15',
                'kelompok_kapasitas'=>'required|in:buket_custom','batas'=>'required|integer|between:0,15']);
        }
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
            'kelompok_kapasitas' => 'required|string|max:30',
            'batas' => 'required|integer|between:0,65535',
        ]);

        $key = $request->only('tanggal', 'kelompok_kapasitas');
        $limit = (int) $request->batas;
        abort_if($key['kelompok_kapasitas'] === 'buket_custom' && $limit > 15, 422);
        \Illuminate\Support\Facades\DB::transaction(function () use ($key, $limit, $request, $editor) {
            $table = \Illuminate\Support\Facades\DB::table('kapasitas_produksi_harian');
            $created = $table->insertOrIgnore($key + ['batas' => $limit, 'dibuat_pada' => now()]);
            $current = $table->where($key)->lockForUpdate()->first();
            if ($editor && ((!$created && (int)$current->batas !== $limit && (int)$current->batas !== $request->integer('sebelum')) || $current->terpakai > $limit)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['batas'=>'Kapasitas berubah atau batas baru kurang dari jumlah terpakai. Periksa nilai terbaru.']);
            }
            if (!$created && (int)$current->batas === $limit) { return; }
            abort_if($current->terpakai > $limit, 409, 'Kapasitas baru di bawah jumlah yang sudah dipesan.');
            $table->update(['batas' => $limit, 'diubah_pada' => now()]);
            \App\Support\StaffAudit::record('ubah_kapasitas','kapasitas_produksi_harian',$current->id,$created ? null : $key + ['batas' => (int) $current->batas], $key + ['batas' => $limit]);
        }, 3);

        return ($editor ? redirect()->route($request->user('staff')->isOwner() && !$request->boolean('operasional') ? 'admin.pengaturan.kalender' : 'admin.kalender',['tanggal'=>$request->tanggal]) : back())
            ->with('success', 'Kapasitas produksi harian berhasil diupdate.');
    }
}
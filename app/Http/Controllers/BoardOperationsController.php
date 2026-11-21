<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BoardOperationsController extends Controller
{
    public function show(int $id)
    {
        $design = DB::table('desain_papan')->where('pesanan_id', $id)->first(['metode','jadwal_serah','judul','penerima','pengirim']);
        abort_unless($design, 404);
        return response()->json($design)->header('Cache-Control', 'private, no-store');
    }

    public function edit(Request $request, int $id)
    {
        $data = $request->validate(['judul' => 'required|string|max:25', 'penerima' => 'required|string|max:45', 'pengirim' => 'required|string|max:40']);
        DB::transaction(function () use ($request, $id, $data) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $design = DB::table('desain_papan')->where('pesanan_id', $id);
            $current = $design->first();
            abort_unless($current, 404);
            abort_unless(in_array($order->status, ['menunggu_pembayaran','dikonfirmasi'], true), 409, 'Teks sudah terkunci.');
            if ($request->has('versi')) {
                abort_unless($request->input('versi') === \App\Support\BoardTextVersion::of($current), 409, 'Teks papan sudah berubah. Muat ulang rincian sebelum menyunting.');
            }
            $design->update($data + ['editor_id' => $request->user('staff')->id, 'diubah_pada' => now()]);
            DB::table('log_aktivitas')->insert(['aksi' => 'ubah_teks_papan', 'pengguna_id' => $request->user('staff')->id,
                'subjek_tabel' => 'pesanan', 'subjek_id' => $id, 'dibuat_pada' => now()]);
        }, 3);
        if (!$request->expectsJson() && $request->input('asal') === 'rincian-admin') {
            return back()->withFragment('teks-papan')->with('status_papan','Teks papan tersimpan.');
        }
        return response()->noContent();
    }

    public function customerEdit(Request $request, string $nomor)
    {
        $data = $request->validate(['judul'=>'required|string|max:25','penerima'=>'required|string|max:45','pengirim'=>'required|string|max:40']);
        $customer = $request->user('customer');
        DB::transaction(function () use ($customer,$nomor,$data) {
            $order = $customer->pesanan()->where('nomor',$nomor)->lockForUpdate()->firstOrFail();
            $design = DB::table('desain_papan')->where('pesanan_id',$order->id);
            abort_unless($design->exists(),404);
            abort_unless(in_array($order->status,['menunggu_pembayaran','dikonfirmasi'],true),409,'Teks sudah terkunci.');
            $design->update($data + ['editor_id'=>null,'diubah_pada'=>now()]);
            DB::table('log_aktivitas')->insert(['aksi'=>'ubah_teks_papan','pelanggan_id'=>$customer->id,'subjek_tabel'=>'pesanan','subjek_id'=>$order->id,'dibuat_pada'=>now()]);
        },3);
        return $request->expectsJson() ? response()->noContent() : redirect()->route('pesanan.show',$nomor)->with('status','Teks papan berhasil disimpan.');
    }

    public function extend(Request $request, int $id)
    {
        $d = $request->validate(['kunci' => 'required|uuid', 'jam' => 'required|integer|between:1,5', 'alasan' => 'required|string|max:200']);
        return DB::transaction(function () use ($request, $id, $d) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $id)->exists(), 404);
            $key = strtolower($d['kunci']);
            $old = DB::table('perpanjangan_sewa')->where(['pesanan_id' => $id, 'kunci' => $key])->first();
            if ($old) {
                abort_unless($old->jam == $d['jam'] && $old->alasan === $d['alasan'], 409, 'Kunci sudah digunakan.');
                return response()->json(['nominal' => (int) $old->nominal, 'batas_baru' => $old->batas_baru]);
            }
            abort_unless($order->status === 'sudah_diserahkan' && $order->total === $order->jumlah_dibayar + $order->saldo, 409);
            $used = (int) DB::table('perpanjangan_sewa')->where('pesanan_id', $id)->sum('jam');
            $end = $order->tanggal_serah->copy()->setTime(18, 0)->addHours($used);
            $hours = (int) $d['jam'];
            abort_if(now('Asia/Jakarta')->gt($end) || $used + $hours > 5, 409, 'Tidak boleh berlaku surut atau melampaui hari sewa.');
            $items = $order->items()->get();
            $held = DB::table('penguasaan_unit')->where('pesanan_id', $id)->pluck('unit_sewa_id');
            abort_unless($items->isNotEmpty() && $held->count() === $items->count() && $items->every(fn ($i) => $held->contains($i->unit_sewa_id) && $i->jumlah == 1), 409);
            $amount = $items->sum(fn ($i) => intdiv($i->harga_satuan + 1999, 2000) * 1000 * $hours);
            abort_if($amount < 1 || $order->total + $amount > 2147482648, 422);
            $adjust = DB::table('penyesuaian_pesanan')->insertGetId(['pesanan_id' => $id, 'jenis' => 'perpanjangan', 'nominal' => $amount,
                'keterangan' => $d['alasan'], 'pengguna_id' => $request->user('staff')->id, 'dibuat_pada' => now()]);
            $newEnd = $end->addHours($hours)->toDateTimeString();
            DB::table('perpanjangan_sewa')->insert(['pesanan_id' => $id, 'kunci' => $key, 'jam' => $hours, 'alasan' => $d['alasan'],
                'nominal' => $amount, 'penyesuaian_id' => $adjust, 'batas_baru' => $newEnd]);
            $order->update(['total' => $order->total + $amount, 'saldo' => $order->saldo + $amount, 'penyesuaian' => $order->penyesuaian + $amount]);
            return response()->json(['nominal' => $amount, 'batas_baru' => $newEnd], 201);
        }, 3);
    }

    public function block(Request $request, int $unit)
    {
        $d = $request->validate(['tanggal' => 'required|date_format:Y-m-d|after_or_equal:today', 'alasan' => 'required|string|max:160']);
        return DB::transaction(function () use ($request, $unit, $d) {
            abort_unless(DB::table('unit_sewa')->where('id', $unit)->lockForUpdate()->first(), 404);
            $key = ['unit_sewa_id' => $unit, 'tanggal' => $d['tanggal']];
            $old = DB::table('blokir_unit_harian')->where($key)->first();
            if ($old) {
                abort_unless($old->alasan === $d['alasan'], 409, 'Alasan blokir berbeda.');
                $id = $old->id;
            } else {
                $id = DB::table('blokir_unit_harian')->insertGetId($key + ['alasan' => $d['alasan'], 'pengguna_id' => $request->user('staff')->id, 'dibuat_pada' => now()]);
                $this->auditBlock($request, $id, 'blokir_unit', $key);
            }
            $conflicts = DB::table('hari_reservasi as h')->join('pesanan as p', 'p.id', '=', 'h.pesanan_id')->where('h.tanggal', $d['tanggal'])->where('h.unit_sewa_id', $unit)
                ->whereIn('p.status', ['menunggu_pembayaran','dikonfirmasi','sedang_diproduksi','siap','sudah_diserahkan'])->pluck('p.nomor');
            return response()->json(['blokir_id' => $id, 'peringatan_pesanan_aktif' => $conflicts], $old ? 200 : 201);
        }, 3);
    }

    public function unblock(Request $request, int $unit, int $blokir)
    {
        DB::transaction(function () use ($request, $unit, $blokir) {
            abort_unless(DB::table('unit_sewa')->where('id', $unit)->lockForUpdate()->first(), 404);
            $query = DB::table('blokir_unit_harian')->where(['id' => $blokir, 'unit_sewa_id' => $unit]);
            $old = $query->first();
            abort_unless($old, 404);
            $query->delete();
            $this->auditBlock($request, $blokir, 'buka_blokir_unit', ['unit_sewa_id' => $unit, 'tanggal' => $old->tanggal]);
        }, 3);
        return response()->noContent();
    }

    private function auditBlock(Request $request, int $id, string $action, array $key): void
    {
        DB::table('log_aktivitas')->insert(['aksi' => $action, 'subjek_tabel' => 'blokir_unit_harian', 'subjek_id' => $id,
            'pengguna_id' => $request->user('staff')->id, 'sesudah' => json_encode($key, JSON_THROW_ON_ERROR), 'dibuat_pada' => now()]);
    }
}

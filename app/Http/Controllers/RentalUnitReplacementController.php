<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RentalUnitReplacementController extends Controller
{
    public function show(Request $request, int $id, int $item)
    {
        $order = Pesanan::findOrFail($id);
        abort_unless(DB::table('checkout_sewa')->where('pesanan_id',$id)->exists(),404);
        $line = $order->items()->whereKey($item)->whereNotNull('unit_sewa_id')->firstOrFail();
        $current = DB::table('unit_sewa')->where('id',$line->unit_sewa_id)->value('kode');
        $day = $order->tanggal_serah->toDateString();
        $locked = !in_array($order->status,['dikonfirmasi','siap'],true) || $order->tanggal_serah->lt(today('Asia/Jakarta'));
        $pending = DB::table('permohonan_batal')->where(['pesanan_id'=>$id,'status'=>'menunggu'])->exists();
        $choices = null;
        if (!$locked && !$pending) {
            $choices = DB::table('unit_sewa as u')->where('u.varian_produk_id',$line->varian_produk_id)
                ->where('u.id','<>',$line->unit_sewa_id)->where('u.status','aktif');
            foreach (['blokir_unit_harian','hari_reservasi','penguasaan_unit'] as $table) {
                $choices->whereNotExists(function ($q) use ($table,$day) {
                    $q->selectRaw('1')->from($table)->whereColumn($table.'.unit_sewa_id','u.id');
                    if ($table !== 'penguasaan_unit') { $q->where($table.'.tanggal',$day); }
                });
            }
            $choices = $choices->orderBy('u.kode')->orderBy('u.id')->paginate(20,['u.id','u.kode'],'unit_page')->withQueryString();
        }
        $history = DB::table('penggantian_unit as g')->join('unit_sewa as a','a.id','=','g.unit_lama_id')
            ->join('unit_sewa as b','b.id','=','g.unit_baru_id')->where('g.pesanan_id',$id)->where('g.item_id',$item)
            ->orderByDesc('g.id')->paginate(20,['a.kode as lama','b.kode as baru','g.alasan','g.dicatat_pada','g.pengguna_id'],'riwayat_page')->withQueryString();
        $context = $request->only(['q','status','tanggal','pelanggan','page','asal']);
        return response()->view('admin.pesanan.unit',compact('order','line','current','locked','pending','choices','history','context'))->header('Cache-Control','private, no-store');
    }

    public function __invoke(Request $request, int $id)
    {
        $d = $request->validate(['kunci' => 'required|uuid', 'item_id' => 'required|integer|min:1', 'unit_lama_id' => 'required|integer|min:1',
            'unit_baru_id' => 'required|integer|min:1|different:unit_lama_id', 'alasan' => 'required|string|max:300']);
        $key = strtolower($d['kunci']);
        try {
            return DB::transaction(function () use ($request, $id, $d, $key) {
                $order = Pesanan::lockForUpdate()->findOrFail($id);
                abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $id)->exists(), 404);
                $item = $order->items()->whereKey($d['item_id'])->lockForUpdate()->firstOrFail();
                $old = DB::table('penggantian_unit')->where('pesanan_id', $id)->where('kunci', $key)->first();
                if ($old) {
                    abort_unless($old->item_id == $item->id && $old->unit_lama_id == $d['unit_lama_id'] && $old->unit_baru_id == $d['unit_baru_id'] && $old->alasan === $d['alasan'], 409, 'Kunci penggantian sudah digunakan.');
                    return response()->json(['penggantian_id' => $old->id]);
                }
                abort_unless(in_array($order->status, ['dikonfirmasi','siap'], true) && $item->unit_sewa_id == $d['unit_lama_id'] && $order->tanggal_serah->gte(today('Asia/Jakarta')), 409, 'Unit/status pesanan sudah berubah.');
                abort_if(DB::table('permohonan_batal')->where(['pesanan_id' => $id, 'status' => 'menunggu'])->exists(), 409, 'Ada permohonan pembatalan.');
                $units = DB::table('unit_sewa')->where('varian_produk_id', $item->varian_produk_id)->whereIn('id', [$d['unit_lama_id'], $d['unit_baru_id']])
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                abort_unless($units->count() === 2 && $units[$d['unit_baru_id']]->status === 'aktif', 409, 'Pilih unit aktif dari varian yang sama.');
                $day = $order->tanggal_serah->toDateString();
                abort_if(DB::table('blokir_unit_harian')->where(['tanggal' => $day, 'unit_sewa_id' => $d['unit_baru_id']])->lockForUpdate()->first() || DB::table('penguasaan_unit')->where('unit_sewa_id', $d['unit_baru_id'])->exists() || DB::table('hari_reservasi')->where(['tanggal' => $day, 'unit_sewa_id' => $d['unit_baru_id']])->exists(), 409, 'Unit baru belum tersedia.');
                $updated = DB::table('hari_reservasi')->where(['pesanan_id' => $id, 'item_pesanan_id' => $item->id, 'tanggal' => $day, 'unit_sewa_id' => $item->unit_sewa_id])->update(['unit_sewa_id' => $d['unit_baru_id']]);
                abort_unless($updated === 1, 409, 'Reservasi awal tidak cocok.');
                DB::table('item_pesanan')->where('id', $item->id)->update(['unit_sewa_id' => $d['unit_baru_id'], 'diubah_pada' => now()]);
                $log = DB::table('penggantian_unit')->insertGetId(['pesanan_id' => $id, 'item_id' => $item->id, 'unit_lama_id' => $d['unit_lama_id'], 'unit_baru_id' => $d['unit_baru_id'],
                    'kunci' => $key, 'alasan' => $d['alasan'], 'pengguna_id' => $request->user('staff')->id, 'dicatat_pada' => now()]);
                return response()->json(['penggantian_id' => $log], 201);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Reservasi untuk unit ini sudah berubah.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

/**
 * Staff Delivery Controller - Delivery/kurir management
 * --------------------------------------------------------
 * guard:staff middleware applied at route level.
 * K-07: Staff Kurir - handover, receive return
 */
class StaffDeliveryController extends BaseController
{
    /**
     * Display the delivery index.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $filters = $request->validate(['tanggal' => 'sometimes|date_format:Y-m-d', 'tugas' => 'sometimes|in:antar,ambil,jemput','nomor'=>'nullable|string|max:30']);
        $date = $filters['tanggal'] ?? today('Asia/Jakarta')->toDateString();
        $task = "CASE WHEN p.status='sudah_diserahkan' THEN 'jemput' WHEN p.cara_serah='ambil' THEN 'ambil' ELSE 'antar' END";
        $time = "CASE WHEN p.status='sudah_diserahkan' THEN COALESCE((SELECT MAX(x.batas_baru) FROM perpanjangan_sewa x WHERE x.pesanan_id=p.id), TIMESTAMP(p.tanggal_serah,'18:00:00')) ELSE COALESCE(d.jadwal_serah,TIMESTAMP(p.tanggal_serah,p.jam_serah_mulai)) END";
        $orders = Pesanan::from('pesanan as p')->leftJoin('desain_papan as d', 'd.pesanan_id', '=', 'p.id')
            ->leftJoin('checkout_buket as b', 'b.pesanan_id', '=', 'p.id')
            ->select('p.id','p.nomor','p.status','p.tanggal_serah','p.cara_serah','p.tunai_saat_serah','p.saldo','p.nomor_wa_pemesan','p.alamat_antar','b.pesanan_id as buket_online')
            ->selectRaw("$task AS tugas, $time AS jadwal")
            ->where(function ($q) use ($date) {
                $q->where(fn ($p) => $p->where('p.tanggal_serah', $date)->where('p.status','siap'))
                    ->orWhere(fn ($p) => $p->where('p.tanggal_serah','<=',$date)->where('p.status','sudah_diserahkan')
                        ->whereExists(fn ($c) => $c->selectRaw('1')->from('penguasaan_unit')->whereColumn('pesanan_id','p.id')));
            })->when(isset($filters['tugas']), fn ($q) => $q->whereRaw("$task = ?", [$filters['tugas']]))
            ->when(filled($filters['nomor'] ?? null),fn($q)=>$q->whereRaw("p.nomor LIKE ? ESCAPE '!'",['%'.strtr($filters['nomor'],['!'=>'!!','%'=>'!%','_'=>'!_']).'%']))
            ->orderBy('p.tanggal_serah')->orderByRaw('jadwal IS NULL')->orderBy('jadwal')->orderBy('p.id')->paginate(20)->withQueryString();
        foreach ($orders as $order) {
            $order->peta = $order->tugas !== 'ambil' && filled($order->alamat_antar)
                ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($order->alamat_antar) : null;
        }
        $rentalUnits = collect();
        $pending = collect();
        if (!$request->expectsJson() && config('checkout.staging') && app()->environment('local','testing','staging')) {
            $ids = $orders->pluck('id');
            $rentalUnits = DB::table('item_pesanan as i')->join('checkout_sewa as c','c.pesanan_id','=','i.pesanan_id')
                ->join('unit_sewa as u','u.id','=','i.unit_sewa_id')
                ->leftJoin('inspeksi_sewa as awal',fn($join)=>$join->on('awal.item_pesanan_id','=','i.id')->where('awal.tahap','serah'))
                ->whereIn('i.pesanan_id',$ids)->orderBy('i.id')
                ->get(['i.pesanan_id','i.id','i.nama_produk','i.nama_varian','u.kode','awal.kondisi as kondisi_serah','awal.catatan as catatan_serah'])->groupBy('pesanan_id');
            $pending = DB::table('permohonan_batal')->whereIn('pesanan_id',$ids)->where('status','menunggu')->pluck('pesanan_id');
        }
        $data = ['judul' => 'Agenda serah terima', 'tanggal' => $date, 'pesanan' => $orders];
        return ($request->expectsJson() ? response()->json($data) : response()->view('staf.agenda.index', $data+compact('rentalUnits','pending')))->header('Cache-Control','private, no-store');
    }

    public function handover(Request $request, \App\Actions\InspectRental $inspection)
    {
        return response()->json($inspection->handle($request, false));
    }

    public function receiveReturn(Request $request, \App\Actions\InspectRental $inspection)
    {
        return response()->json($inspection->handle($request, true));
    }
}

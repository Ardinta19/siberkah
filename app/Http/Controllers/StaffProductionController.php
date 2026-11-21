<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffProductionController extends Controller
{
    public function index(Request $request)
    {
        $filters = array_filter($request->validate(['tahap'=>'nullable|in:dikonfirmasi,sedang_diproduksi','tanggal_serah'=>'nullable|date_format:Y-m-d','nomor'=>'nullable|string|max:30']),fn($v)=>$v!==null && $v!=='');
        $status = $filters['tahap'] ?? null;
        $orders = Pesanan::whereIn('status', ['dikonfirmasi', 'sedang_diproduksi'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(isset($filters['tanggal_serah']),fn($q)=>$q->where('tanggal_serah',$filters['tanggal_serah']))
            ->when(isset($filters['nomor']),fn($q)=>$q->whereRaw("nomor LIKE ? ESCAPE '!'",['%'.strtr($filters['nomor'],['!'=>'!!','%'=>'!%','_'=>'!_']).'%']))
            ->orderBy('tanggal_serah')->orderBy('id')->paginate(20, ['id','nomor','status','tanggal_serah','catatan_pelanggan'])->appends($filters);
        $ids = $orders->pluck('id');
        $items = DB::table('item_pesanan')->join('produk','produk.id','=','item_pesanan.produk_id')->whereIn('pesanan_id', $ids)->orderBy('item_pesanan.id')->get(['item_pesanan.id','pesanan_id','nama_produk','nama_varian','jumlah','tulisan_kustom','produk.jenis']);
        $options = DB::table('opsi_item_pesanan')->whereIn('item_pesanan_id', $items->pluck('id'))->orderBy('id')->get(['item_pesanan_id','nama_opsi','label_nilai','teks_bebas'])->groupBy('item_pesanan_id');
        foreach ($items as $item) { $item->opsi = $options->get($item->id, collect()); }
        $grouped = $items->groupBy('pesanan_id');
        $boards = DB::table('desain_papan')->whereIn('pesanan_id', $ids)->get(['pesanan_id','metode','judul','penerima','pengirim'])->keyBy('pesanan_id');
        $snapshots = DB::table('checkout_buket')->whereIn('pesanan_id', $ids)->pluck('jumlah_custom','pesanan_id');
        foreach ($orders as $order) {
            $order->rincian = $grouped->get($order->id, collect());
            $order->papan = $boards->get($order->id);
            $order->langkah = $this->nextStep($order->status, $order->rincian->pluck('jenis'), $snapshots->get($order->id), $order->papan);
        }
        $data = ['judul' => 'Antrean produksi', 'produksi' => true, 'filters'=>$filters, 'pesanan' => $orders];
        return ($request->expectsJson() ? response()->json($data) : response()->view('staf.agenda.index', $data))->header('Cache-Control','private, no-store');
    }

    public function startProduction(Request $request)
    {
        return $this->transition($request, true);
    }

    public function markReady(Request $request)
    {
        return $this->transition($request, false);
    }

    // The list and the locked write must describe the same allowed next step.
    private function nextStep(string $status, $types, $snapshot, $board): array
    {
        if ($types->isEmpty()) {
            return ['kode'=>null, 'alasan'=>'Pesanan belum memiliki item.'];
        }
        $custom = $board || ($snapshot !== null ? $snapshot > 0 : $types->contains('custom'));
        if ($status === 'dikonfirmasi' && $board && (!$board->judul || !$board->penerima || !$board->pengirim)) {
            return ['kode'=>null, 'alasan'=>'Lengkapi teks papan sebelum produksi.'];
        }
        return ['kode'=>match ($status) {
            'dikonfirmasi' => $custom ? 'T05' : 'T06',
            'sedang_diproduksi' => 'T07',
            default => null,
        }, 'alasan'=>null];
    }

    private function transition(Request $request, bool $start)
    {
        $id = $request->validate(['pesanan_id' => 'required|integer|min:1'])['pesanan_id'];
        DB::transaction(function () use ($id, $start, $request) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $types = $order->items()->join('produk', 'produk.id', '=', 'item_pesanan.produk_id')->pluck('produk.jenis');
            $snapshot = DB::table('checkout_buket')->where('pesanan_id', $id)->value('jumlah_custom');
            $board = DB::table('desain_papan')->where('pesanan_id', $id)->first();
            $from = $order->status;
            $step = $this->nextStep($from, $types, $snapshot, $board);
            $code = $step['kode'];
            abort_unless($code && ($start === ($code === 'T05')), 409, $step['alasan'] ?? 'Status atau jenis item tidak sesuai untuk aksi ini.');
            $order->update(['status' => $start ? 'sedang_diproduksi' : 'siap']);
            DB::table('riwayat_status_pesanan')->insert([
                'pesanan_id' => $id, 'status_lama' => $from, 'status_baru' => $order->status,
                'kode_transisi' => $code, 'pelaku' => 'staf', 'pengguna_id' => $request->user('staff')->id,
                'dibuat_pada' => now(),
            ]);
        }, 3);
        return back()->with('status', 'Status produksi diperbarui.');
    }
}

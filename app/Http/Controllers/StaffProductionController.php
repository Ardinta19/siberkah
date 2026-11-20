<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffProductionController extends Controller
{
    public function index(Request $request)
    {
        $orders = Pesanan::whereIn('status', ['dikonfirmasi', 'sedang_diproduksi'])
            ->orderBy('tanggal_serah')->orderBy('id')->paginate(20, ['id','nomor','status','tanggal_serah','catatan_pelanggan']);
        $ids = $orders->pluck('id');
        $items = DB::table('item_pesanan')->whereIn('pesanan_id', $ids)->orderBy('id')->get(['id','pesanan_id','nama_produk','nama_varian','jumlah','tulisan_kustom']);
        $options = DB::table('opsi_item_pesanan')->whereIn('item_pesanan_id', $items->pluck('id'))->orderBy('id')->get(['item_pesanan_id','nama_opsi','label_nilai','teks_bebas'])->groupBy('item_pesanan_id');
        foreach ($items as $item) { $item->opsi = $options->get($item->id, collect()); }
        $grouped = $items->groupBy('pesanan_id');
        $boards = DB::table('desain_papan')->whereIn('pesanan_id', $ids)->get(['pesanan_id','metode','judul','penerima','pengirim'])->keyBy('pesanan_id');
        foreach ($orders as $order) {
            $order->rincian = $grouped->get($order->id, collect());
            $order->papan = $boards->get($order->id);
        }
        $data = ['judul' => 'Antrean produksi', 'produksi' => true, 'pesanan' => $orders];
        return ($request->expectsJson() ? response()->json($data) : response()->view('staf.agenda', $data))->header('Cache-Control','private, no-store');
    }

    public function startProduction(Request $request)
    {
        return $this->transition($request, true);
    }

    public function markReady(Request $request)
    {
        return $this->transition($request, false);
    }

    private function transition(Request $request, bool $start)
    {
        $id = $request->validate(['pesanan_id' => 'required|integer|min:1'])['pesanan_id'];
        DB::transaction(function () use ($id, $start, $request) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $types = $order->items()->join('produk', 'produk.id', '=', 'item_pesanan.produk_id')->pluck('produk.jenis');
            abort_if($types->isEmpty(), 409, 'Pesanan belum memiliki item.');
            $snapshot = DB::table('checkout_buket')->where('pesanan_id', $id)->value('jumlah_custom');
            $custom = $snapshot !== null ? $snapshot > 0 : $types->contains('custom');
            $board = DB::table('desain_papan')->where('pesanan_id', $id)->first();
            if ($board) {
                $custom = true;
                abort_if($start && (! $board->judul || ! $board->penerima || ! $board->pengirim), 409, 'Lengkapi teks papan sebelum produksi.');
            }
            $from = $order->status;
            $code = match (true) {
                $start && $from === 'dikonfirmasi' && $custom => 'T05',
                ! $start && $from === 'dikonfirmasi' && ! $custom => 'T06',
                ! $start && $from === 'sedang_diproduksi' => 'T07',
                default => abort(409, 'Status atau jenis item tidak sesuai untuk aksi ini.'),
            };
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

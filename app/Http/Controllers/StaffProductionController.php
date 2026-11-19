<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffProductionController extends Controller
{
    public function index()
    {
        return view('staf.agenda', [
            'judul' => 'Antrean produksi',
            'produksi' => true,
            'pesanan' => Pesanan::whereIn('status', ['dikonfirmasi', 'sedang_diproduksi'])
                ->orderBy('tanggal_serah')->orderBy('id')->paginate(20),
        ]);
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
            $custom = $types->contains('custom');
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

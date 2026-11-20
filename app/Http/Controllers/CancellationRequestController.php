<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A request is not an order transition. Only an approved T12 releases the rental. */
final class CancellationRequestController extends Controller
{
    private function requireStaging(): void
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Permohonan pembatalan belum dibuka.');
    }

    public function submit(Request $request, string $nomor)
    {
        $this->requireStaging();
        $reason = $request->validate(['alasan' => 'required|string|max:300'])['alasan'];
        [$status, $created] = DB::transaction(function () use ($request, $nomor, $reason) {
            $customer = $request->user('customer');
            $order = $customer->pesanan()->where('nomor', $nomor)->lockForUpdate()->firstOrFail();
            abort_unless(DB::table('checkout_bank')->where('pesanan_id', $order->id)->exists(), 404);
            $existing = DB::table('permohonan_batal')->where('pesanan_id', $order->id)->first();
            if ($existing) {
                abort_unless($existing->alasan === $reason, 409, 'Permohonan sudah tercatat.');
                return [$existing->status, false];
            }
            abort_unless(in_array($order->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap'], true) && ($order->jumlah_dibayar > 0 || $order->tunai_saat_serah),
                409, 'Hanya pesanan berbayar sebelum serah terima yang dapat diajukan.');
            DB::table('permohonan_batal')->insert(['pesanan_id' => $order->id, 'pelanggan_id' => $customer->id, 'alasan' => $reason, 'diajukan_pada' => now()]);
            return ['menunggu', true];
        }, 3);
        return response()->json(['status_permohonan' => $status], $created ? 201 : 200);
    }

    public function show(Request $request, string $nomor)
    {
        $this->requireStaging();
        $order = $request->user('customer')->pesanan()->where('nomor', $nomor)->firstOrFail();
        $row = DB::table('permohonan_batal')->where('pesanan_id', $order->id)->first(['status', 'alasan', 'diajukan_pada', 'diputuskan_pada', 'catatan_keputusan']);
        abort_unless($row, 404);
        return response()->json($row)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        $this->requireStaging();
        return response()->json(DB::table('permohonan_batal as b')->join('pesanan as p', 'p.id', '=', 'b.pesanan_id')
            ->where('b.status', 'menunggu')->orderBy('b.diajukan_pada')->orderBy('b.pesanan_id')
            ->paginate(25, ['b.pesanan_id', 'p.nomor', 'p.status as status_pesanan', 'b.alasan', 'b.diajukan_pada']))->header('Cache-Control', 'private, no-store');
    }

    public function reject(Request $request, int $id)
    {
        $this->requireStaging();
        $note = $request->validate(['alasan' => 'required|string|max:300'])['alasan'];
        DB::transaction(function () use ($request, $id, $note) {
            Pesanan::lockForUpdate()->findOrFail($id);
            $query = DB::table('permohonan_batal')->where('pesanan_id', $id);
            $row = $query->first();
            abort_unless($row, 404);
            if ($row->status === 'ditolak' && $row->catatan_keputusan === $note) {
                return;
            }
            abort_unless($row->status === 'menunggu', 409, 'Permohonan sudah diputuskan.');
            $query->update(['status' => 'ditolak', 'peninjau_id' => $request->user('staff')->id, 'catatan_keputusan' => $note, 'diputuskan_pada' => now()]);
        }, 3);
        return response()->json(['status_permohonan' => 'ditolak']);
    }
}

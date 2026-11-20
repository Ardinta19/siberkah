<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CustomerPaymentController extends Controller
{
    private function ownedOrder(Request $request, string $nomor)
    {
        return $request->user('customer')->pesanan()->where('nomor', $nomor)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('checkout_bank')->whereColumn('pesanan_id', 'pesanan.id'));
    }

    // A null deadline is valid only for the actual post-return closing invoice.
    private function window($order, object $bill, $now): array
    {
        if ($bill->status !== 'menunggu' || $bill->tunai_saat_serah || $bill->dikonfirmasi_pelanggan_pada) {
            return [false, null];
        }
        if (DB::table('mutasi_masuk')->where(['pembayaran_id' => $bill->id, 'hasil' => 'perlu_rekonsiliasi'])->exists()
            || DB::table('permohonan_batal')->where(['pesanan_id' => $order->id, 'status' => 'menunggu'])->exists()) {
            return [false, null];
        }
        if ($bill->jenis === 'pelunasan' && $order->status === 'sudah_dikembalikan') {
            return [DB::table('penutupan_sewa')->where(['pesanan_id' => $order->id, 'tagihan_id' => $bill->id])->exists(), null];
        }
        $balance = $bill->jenis === 'pelunasan';
        $eligible = $balance ? in_array($order->status, ['dikonfirmasi','sedang_diproduksi','siap'], true)
            : (in_array($bill->jenis, ['dp','penuh'], true) && $order->status === 'menunggu_pembayaran');
        $end = $balance ? $order->batas_pelunasan_pada : $order->tahan_hingga_pada;
        return [$eligible && $end && $now->lt($end), $eligible ? $end : null];
    }

    public function show(Request $request, string $nomor)
    {
        $order = $this->ownedOrder($request, $nomor)->firstOrFail();
        $invoices = DB::table('pembayaran')->where('pesanan_id', $order->id)->orderBy('id')->get(['id','jenis','tunai_saat_serah','status','tagihan','kode_unik','nominal_diharapkan','dikonfirmasi_pelanggan_pada','diverifikasi_pada']);
        $decisions = DB::table('keputusan_selisih as k')->join('mutasi_masuk as m', 'm.id', '=', 'k.mutasi_id')
            ->join('pembayaran as p', 'p.id', '=', 'm.pembayaran_id')->where('p.pesanan_id', $order->id)->orderBy('k.mutasi_id')
            ->get(['m.pembayaran_id', 'k.keputusan', 'k.catatan', 'k.diputuskan_pada']);
        $now = now('Asia/Jakarta');
        $available = false;
        foreach ($invoices as $bill) {
            [$bill->boleh_bayar, $end] = $this->window($order, $bill, $now);
            $bill->batas_wib = $end?->toIso8601String();
            $available = $available || $bill->boleh_bayar;
        }
        $destinations = $available ? DB::table('rekening_pembayaran')->where('aktif', true)->orderBy('urutan')->orderBy('id')
            ->get(['id','jenis','nama_bank','atas_nama','nomor_rekening','qris_png']) : collect();
        foreach ($invoices as $bill) {
            $bill->boleh_bayar = $bill->boleh_bayar && $destinations->isNotEmpty();
        }
        $data = ['nomor' => $nomor, 'status' => $order->status, 'saldo' => $order->saldo, 'tunai_saat_serah' => $order->tunai_saat_serah, 'cara_serah' => $order->cara_serah, 'tahan_hingga_pada' => $order->tahan_hingga_pada,
            'batas_pelunasan_pada' => $order->batas_pelunasan_pada, 'pembayaran' => $invoices, 'keputusan_selisih' => $decisions, 'tujuan' => $destinations, 'waktu_server' => $now->toIso8601String()];
        return ($request->expectsJson() ? response()->json($data) : response()->view('pelanggan.pembayaran.tagihan', $data))->header('Cache-Control', 'private, no-store');
    }

    public function confirm(Request $request, string $nomor)
    {
        $data = $request->validate(['pembayaran_id' => 'required|integer|min:1', 'tujuan_id' => 'required|integer|min:1',
            'bukti' => 'nullable|file|image|mimes:jpeg,png,webp|max:2048|dimensions:max_width=2048,max_height=2048']);
        $proof = $request->hasFile('bukti') ? \App\Support\TransferProof::prepare($request->file('bukti')) : null;
        DB::transaction(function () use ($request, $nomor, $data, $proof) {
            $order = $this->ownedOrder($request, $nomor)->lockForUpdate()->firstOrFail();
            $query = DB::table('pembayaran')->where('pesanan_id', $order->id)->where('id', $data['pembayaran_id'])->where('tunai_saat_serah', false)->whereIn('jenis', ['dp','penuh','pelunasan']);
            $bill = $query->lockForUpdate()->first();
            abort_unless($bill, 404);
            abort_unless($bill->status === 'menunggu', 409, 'Tagihan sudah ditutup.');
            if ($bill->dikonfirmasi_pelanggan_pada) {
                abort_unless((int) $bill->rekening_pembayaran_id === (int) $data['tujuan_id'], 409);
                abort_if($proof && DB::table('bukti_transfer')->where('pembayaran_id',$bill->id)->value('sidik') !== $proof['sidik'], 409, 'Bukti yang sudah dikonfirmasi tidak dapat diganti.');
                return;
            }
            $destination = DB::table('rekening_pembayaran')->where('id', $data['tujuan_id'])->sharedLock()->first();
            abort_unless($destination && $destination->aktif, 409, 'Tujuan tidak aktif. Jika sudah membayar, hubungi Admin; jangan bayar lagi.');
            abort_unless($this->window($order, $bill, now())[0], 409, 'Tagihan tidak dapat dikonfirmasi. Jika sudah membayar, hubungi Admin; jangan bayar lagi.');
            if ($proof) { DB::table('bukti_transfer')->insert($proof + ['pembayaran_id' => $bill->id, 'dibuat_pada' => now()]); }
            $query->update(['rekening_pembayaran_id' => $destination->id, 'dikonfirmasi_pelanggan_pada' => now(), 'diubah_pada' => now()]);
        }, 3);
        if (! $request->expectsJson()) {
            return redirect()->route('payment.show', $nomor);
        }
        return response()->json(['hasil' => 'menunggu_pemeriksaan'], 202);
    }
}

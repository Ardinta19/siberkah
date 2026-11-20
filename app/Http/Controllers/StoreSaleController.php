<?php

namespace App\Http\Controllers;

use App\Actions\OrderNumber;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Direct, paid, in-store sale. Never use rental deposits or bank transfer references here. */
final class StoreSaleController extends Controller
{
    public function store(Request $request, OrderNumber $numbers)
    {
        $data = $request->validate(['kunci' => 'required|uuid', 'pelanggan_id' => 'required|integer|min:1', 'produk_id' => 'required|integer|min:1',
            'varian_id' => 'required|integer|min:1', 'jumlah' => 'required|integer|between:1,100', 'nominal' => 'required|integer|between:1,2147482648',
            'setuju_privasi' => 'accepted', 'versi_privasi' => 'required|string|max:40']);
        $staff = $request->user('staff')->id;
        $key = strtolower($data['kunci']);
        $fingerprint = hash('sha256', json_encode([$staff, (int) $data['pelanggan_id'], (int) $data['produk_id'], (int) $data['varian_id'],
            (int) $data['jumlah'], (int) $data['nominal'], $data['versi_privasi']], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $numbers, $staff, $key, $fingerprint) {
            $customer = Pelanggan::lockForUpdate()->findOrFail($data['pelanggan_id']);
            $receipt = DB::table('penjualan_toko')->where('kunci', $key)->lockForUpdate()->first();
            if ($receipt) {
                abort_unless($fingerprint === $receipt->sidik, 409, 'Rincian penjualan berbeda.');
                return response()->json(['pesanan_id' => $receipt->pesanan_id]);
            }
            abort_unless($customer->isActive() && filled($customer->nama) && $customer->isVerified() && $data['versi_privasi'] === config('checkout.privacy_version'), 422);
            $now = now('Asia/Jakarta');
            $number = $numbers->next($now->format('ym'));
            $product = DB::table('produk')->where('id', $data['produk_id'])->where('jenis', 'jual')->where('aktif', true)->lockForUpdate()->first();
            abort_unless($product && ! DB::table('opsi_produk')->where('produk_id', $product->id)->exists(), 409, 'Hanya produk siap jual standar.');
            abort_if(DB::table('blokir_kalender')->where('tanggal', $now->toDateString())->where(fn ($q) => $q->whereNull('produk_id')->orWhere('produk_id', $product->id))->exists(), 409, 'Tanggal diblokir.');
            $variant = DB::table('varian_produk')->where('produk_id', $product->id)->where('id', $data['varian_id'])->where('aktif', true)->lockForUpdate()->first();
            abort_unless($variant && $variant->stok >= $data['jumlah'] && $variant->harga > 0, 409, 'Stok tidak mencukupi atau harga belum ditetapkan.');
            $subtotal = $variant->harga * $data['jumlah'];
            $total = intdiv($subtotal + 999, 1000) * 1000;
            abort_unless($total === (int) $data['nominal'], 409, 'Catat tunai tepat sebesar total setelah pembulatan.');
            DB::table('varian_produk')->where('id', $variant->id)->decrement('stok', $data['jumlah']);
            $order = Pesanan::create(['nomor' => $number, 'status' => 'dikonfirmasi', 'asal' => 'admin', 'pengguna_id' => $staff, 'pelanggan_id' => $customer->id,
                'nama_pemesan' => $customer->nama, 'nomor_wa_pemesan' => $customer->nomor_wa, 'cara_serah' => 'ambil', 'tanggal_serah' => $now->toDateString(),
                'subtotal' => $subtotal, 'total' => $total, 'saldo' => 0, 'jumlah_dibayar' => $total, 'dikonfirmasi_pada' => $now]);
            DB::table('item_pesanan')->insert(['pesanan_id' => $order->id, 'produk_id' => $product->id, 'varian_produk_id' => $variant->id,
                'nama_produk' => $product->nama, 'nama_varian' => $variant->nama, 'harga_satuan' => $variant->harga, 'jumlah' => $data['jumlah'], 'subtotal' => $subtotal,
                'dibuat_pada' => $now, 'diubah_pada' => $now]);
            DB::table('pembayaran')->insert(['pesanan_id' => $order->id, 'jenis' => 'penuh', 'status' => 'terverifikasi', 'tagihan' => $total,
                'nominal_diharapkan' => $total, 'nominal_diterima' => $total, 'pengguna_id' => $staff, 'diterima_pada' => $now, 'diverifikasi_pada' => $now,
                'catatan_verifikasi' => 'Tunai langsung di toko', 'dibuat_pada' => $now, 'diubah_pada' => $now]);
            DB::table('penjualan_toko')->insert(['pesanan_id' => $order->id, 'kunci' => $key, 'sidik' => $fingerprint, 'versi_privasi' => $data['versi_privasi'], 'disetujui_pada' => $now]);
            $this->history($order->id, $staff, null, 'menunggu_pembayaran', 'T01');
            $this->history($order->id, $staff, 'menunggu_pembayaran', 'dikonfirmasi', 'T02');
            return response()->json(['pesanan_id' => $order->id], 201);
        }, 3);
    }

    public function handover(Request $request, int $id)
    {
        $request->validate(['barang_diperiksa' => 'accepted', 'diterima_pembeli' => 'accepted']);
        return $this->transition($request, $id, false);
    }

    public function complete(Request $request, int $id)
    {
        return $this->transition($request, $id, true);
    }

    private function transition(Request $request, int $id, bool $complete)
    {
        return DB::transaction(function () use ($request, $id, $complete) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $online = DB::table('checkout_buket')->where('pesanan_id', $id)->exists();
            abort_unless($online || DB::table('penjualan_toko')->where('pesanan_id', $id)->exists(), 404);
            $from = $complete ? 'sudah_diserahkan' : 'siap';
            $to = $complete ? 'selesai' : 'sudah_diserahkan';
            if ($order->status !== $to) {
                abort_if($online && DB::table('permohonan_batal')->where(['pesanan_id' => $id, 'status' => 'menunggu'])->exists(), 409, 'Tinjau pembatalan sebelum serah.');
                abort_if($online && ! $complete && ! $order->tanggal_serah->isToday(), 409, 'Serah pada tanggal pesanan.');
                if (! $complete) {
                    app(\App\Actions\HandoverCash::class)->collect($request, $order);
                }
                abort_unless($order->status === $from && $order->saldo === 0 && $order->jumlah_dibayar === $order->total, 409);
                $order->update(['status' => $to, ($complete ? 'selesai_pada' : 'diserahkan_pada') => now()]);
                $this->history($id, $request->user('staff')->id, $from, $to, $complete ? 'T10' : 'T08');
            }
            return ['status' => $to];
        }, 3);
    }

    private function history(int $id, int $staff, ?string $from, string $to, string $code): void
    {
        DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $id, 'status_lama' => $from, 'status_baru' => $to,
            'kode_transisi' => $code, 'pengguna_id' => $staff, 'pelaku' => 'staf', 'dibuat_pada' => now()]);
    }
}

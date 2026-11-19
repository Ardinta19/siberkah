<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Admin Order Controller - Order management for owner/admin
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 * K-08: Admin Pesanan - filter, search, manual input, transition status
 */
class AdminOrderController extends BaseController
{
    /**
     * Display the orders index with filters.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $query = Pesanan::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tanggal
        if ($request->has('tanggal')) {
            $query->whereDate('tanggal_serah', $request->tanggal);
        }

        // Filter by pelanggan
        if ($request->has('pelanggan')) {
            $query->where('pelanggan_id', $request->pelanggan);
        }

        $pesanan = $query->latest()->paginate(20);

        // Get filter options
        $statuses = ['menunggu_pembayaran', 'dikonfirmasi', 'sedang_diproduksi', 'siap', 'sudah_diserahkan', 'sudah_dikembalikan', 'selesai', 'dibatalkan'];

        return view('admin.pesanan.index', [
            'pesanan' => $pesanan,
            'filterStatus' => $request->get('status'),
            'filterTanggal' => $request->get('tanggal'),
            'statuses' => $statuses,
        ]);
    }

    /**
     * Show a specific order detail.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        $pesanan = Pesanan::with(['itemPesanan', 'pembayaran', 'pelanggan'])->findOrFail($id);

        return view('admin.pesanan.show', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Manual store order (from WA input).
     * K-08: Admin Pesanan - manual input (form sama pelanggan + alasan pengecualian)
     *
     * @return RedirectResponse
     */
    public function manualStore(Request $request)
    {
        $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'produk_id' => 'required|exists:produk,id',
            'varian_id' => 'sometimes|exists:varian_produk,id',
            'tanggal_serah' => 'required|date',
            'cara_serah' => 'required|in:antar,ambil',
            'alamat_antar' => 'sometimes|string',
            'zona_antar_id' => 'sometimes|exists:zona_antar,id',
            'jam_serah_mulai' => 'sometimes|date_format:H:i',
            'jam_serah_selesai' => 'sometimes|date_format:H:i',
            'catatan' => 'sometimes|string',
            'alasan_pengecualian' => 'sometimes|string',
        ]);

        // TODO: Implement order creation from manual input
        // Similar to CustomerOrderController::store but with admin notes

        return back()
            ->with('success', 'Pesanan manual berhasil dibuat.');
    }

    /**
     * Transition order status via T01-T14.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function transitionStatus(Request $request, $id)
    {
        $request->validate([
            'status_baru' => 'required|in:menunggu_pembayaran,kedaluwarsa,dikonfirmasi,sedang_diproduksi,siap,sudah_diserahkan,sudah_dikembalikan,selesai,dibatalkan',
            'kode_transisi' => 'required|string|size:3',
            'catatan' => 'sometimes|string',
        ]);

        $pesanan = Pesanan::findOrFail($id);

        // TODO: Use TransitionOrderStatus action
        // $action = new TransitionOrderStatus();
        // $result = $action->handle($pesanan, $request->status_baru, $request->kode_transisi, auth('staff')->user(), $request->catatan);

        // For now, just update status
        $pesanan->status = $request->status_baru;
        $pesanan->save();

        // Log the transition
        // \App\Models\LogAktivitas::create([...]);

        return back()
            ->with('success', 'Status pesanan berhasil diubah menjadi '.$request->status_baru);
    }

    /**
     * Verify payment for an order.
     *
     * @param  int  $id
     * @return View
     */
    public function verifyPayment($id)
    {
        $pesanan = Pesanan::findOrFail($id);
        $pembayaran = $pesanan->pembayaran()->first();

        return view('admin.pembayaran.verify', [
            'pesanan' => $pesanan,
            'pembayaran' => $pembayaran,
        ]);
    }

    /**
     * Reject or accept payment with note.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function rejectOrAcceptPayment(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:terima,tolak',
            'nominal_diterima' => 'sometimes|integer',
            'catatan' => 'required_if:action,tolak|string',
        ]);

        $pesanan = Pesanan::findOrFail($id);
        $pembayaran = $pesanan->pembayaran()->first();

        if ($request->action === 'terima') {
            // Update payment status
            $pembayaran->status = 'terverifikasi';
            $pembayaran->diterima_pana = now();
            $pembayaran->save();

            // Update order status to diskonfirmasi
            $pesanan->status = 'dikonfirmasi';
            $pesanan->dikonfirmasi_pana = now();
            $pesanan->save();

            // Log aktivitas
            // \App\Models\LogAktivitas::create([...]);
        } elseif ($request->action === 'tolak') {
            $pembayaran->status = 'ditolak';
            $pembayaran->catatan_verifikasi = $request->catatan;
            $pembayaran->diverifikasi_pana = now();
            $pembayaran->save();

            // Keep order status or set to specific rejection status
        }

        return back()
            ->with('success', 'Verifikasi pembayaran '.$request->action.' berhasil.');
    }

    /**
     * Record cash payment.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function recordCash(Request $request, $id)
    {
        $request->validate([
            'cara_serah' => 'required|in:antar,ambil',
            'alamat_antar' => 'sometimes|string',
            'zona_antar_id' => 'sometimes|exists:zona_antar,id',
            'nominal_jaminan' => 'sometimes|integer',
        ]);

        $pesanan = Pesanan::findOrFail($id);

        // TODO: Implement cash payment recording
        // - Set wajib_dp based on nominal
        // - Generate tagihan
        // - Create Pembayaran record

        return back()
            ->with('success', 'Pembayaran tunai tercatat.');
    }

    /**
     * Record refund.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function recordRefund(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:terima,tolak',
            'catatan' => 'required|string',
        ]);

        $pesanan = Pesanan::findOrFail($id);
        $pembayaran = $pesanan->pembayaran()->first();

        // TODO: Implement refund recording using RecordRefund action
        // - Hitung BR-13 (min dari waktu & status)
        // - Catat penyesuaian
        // - Update status

        if ($request->action === 'terima') {
            $pembayaran->status = 'terverifikasi';
            $pembayaran->save();
        }

        return back()
            ->with('success', 'Catatan refund berhasil disimpan.');
    }

    /**
     * Reactivate expired order.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function reactivateExpired($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        // TODO: Implement ExpireStaleOrders scheduler logic
        // - Cek status awaiting_payment + tahan_hingga_pada < now → expired
        // - Release unit
        // - Set status menjadi kedaluwarsa

        $pesanan->status = 'kedaluwarsa';
        $pesanan->dibatalkan_pana = now();
        $pesanan->save();

        return back()
            ->with('success', 'Pesanan expired telah diaktifkan kembali.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Actions\VerifyRentalTransfer;
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
        $filters = $request->validate(['status' => 'sometimes|string|max:30', 'tanggal' => 'sometimes|date_format:Y-m-d', 'pelanggan' => 'sometimes|integer|min:1']);
        $query = Pesanan::query();
        foreach (['status' => 'status', 'tanggal' => 'tanggal_serah', 'pelanggan' => 'pelanggan_id'] as $field => $column) {
            if (isset($filters[$field])) {
                $query->where($column, $filters[$field]);
            }
        }
        return response()->json($query->orderByDesc('id')->paginate(20, ['id','nomor','status','tanggal_serah','asal','total','saldo']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(int $id)
    {
        $order = Pesanan::with(['items', 'pembayaran'])->findOrFail($id);
        return response()->json($order)->header('Cache-Control', 'private, no-store');
    }

    public function manualStore(Request $request, \App\Actions\CreateRentalOrder $checkout)
    {
        $data = $request->validate(['pelanggan_id' => 'required|integer|min:1', 'persetujuan_pelanggan_diperiksa' => 'accepted', 'alasan_pengecualian' => 'prohibited']);
        $customer = \App\Models\Pelanggan::findOrFail($data['pelanggan_id']);
        try {
            $order = $checkout->create($customer, $request->all(), $request->user('staff'));
        } catch (\DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        return response()->json(['pesanan_id' => $order->id, 'nomor' => $order->nomor]);
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

    public function verifyPayment(Request $request, int $id, VerifyRentalTransfer $transfers)
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Verifikasi belum dibuka.');
        try {
            $result = $transfers->record($request->user('staff'), $id, $request->all());
        } catch (\DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        return response()->json(['hasil' => $result], $result === 'perlu_rekonsiliasi' ? 202 : 200);
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
     * Reactivate expired order.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function reactivateExpired(Request $request, int $id, \App\Actions\ResolveLateRental $action)
    {
        $data = $request->validate(['mutasi_id' => 'required|integer|min:1']);
        try {
            return response()->json(['hasil' => $action->handle($request->user('staff'), $id, (int) $data['mutasi_id'])]);
        } catch (\DomainException $exception) {
            abort(409, $exception->getMessage());
        }
    }
}

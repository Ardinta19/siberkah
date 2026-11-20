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
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'status' => 'sometimes|string|max:30', 'tanggal' => 'sometimes|date_format:Y-m-d', 'pelanggan' => 'sometimes|integer|min:1']);
        $query = Pesanan::query();
        foreach (['status' => 'status', 'tanggal' => 'tanggal_serah', 'pelanggan' => 'pelanggan_id'] as $field => $column) {
            if (isset($filters[$field])) {
                $query->where($column, $filters[$field]);
            }
        }
        if (filled($filters['q'] ?? null)) {
            $q = '%'.str_replace(['!', '%', '_'], ['!!','!%','!_'], $filters['q']).'%';
            $query->where(fn ($row) => $row->whereRaw("nama_pemesan LIKE ? ESCAPE '!'", [$q])->orWhereRaw("nomor LIKE ? ESCAPE '!'", [$q]));
        }
        return response()->json($query->orderByDesc('id')->paginate(20, ['id','nomor','status','tanggal_serah','asal','total','saldo']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(int $id)
    {
        $order = Pesanan::with(['items', 'pembayaran'])->findOrFail($id);
        $order->setAttribute('tautan_whatsapp', \App\Services\WhatsAppLinks::order($order, true));
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

    public function manualBouquet(Request $request, \App\Actions\CreateBouquetOrder $checkout)
    {
        $d = $request->validate(['pelanggan_id' => 'required|integer|min:1', 'persetujuan_pelanggan_diperiksa' => 'accepted', 'alasan_pengecualian' => 'prohibited']);
        try {
            $order = $checkout->create(\App\Models\Pelanggan::findOrFail($d['pelanggan_id']), $request->all(), $request->user('staff'));
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
        return response()->json(['pesanan_id' => $order->id, 'nomor' => $order->nomor, 'total' => $order->total]);
    }

    /**
     * Transition order status via T01-T14.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function transitionStatus(Request $request, $id)
    {
        abort(410, 'Gunakan aksi khusus sesuai tahap pesanan.');
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
    public function rejectOrAcceptPayment(Request $request, int $id, VerifyRentalTransfer $transfers)
    {
        $data = $request->validate(['mutasi_id' => 'required|integer|min:1', 'action' => 'required|in:terima,tolak', 'catatan' => 'required|string|max:300']);
        try {
            $result = $transfers->decide($request->user('staff'), $id, (int) $data['mutasi_id'], $data['action'], $data['catatan']);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
        return response()->json(['keputusan' => $result]);
    }

    /**
     * Record cash payment.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function recordCash(Request $request, $id)
    {
        abort(410, 'Gunakan aksi khusus sesuai tahap pesanan.');
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

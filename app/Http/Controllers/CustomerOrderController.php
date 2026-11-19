<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Customer Order Controller - Customer order management
 * --------------------------------------------------------
 * guest:customer middleware applied at route level.
 * K-01: Pelanggan bisa melihat, membuat, dan membatalkan pesanan.
 */
class CustomerOrderController extends BaseController
{
    /**
     * Display the customer orders index.
     *
     * @return View
     */
    public function index()
    {
        $pesanan = auth('customer')->user()->pesanan()->latest()->paginate(10);

        return view('pages.customer.orders.index', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Store a new order.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|exists:produk,id',
            'varian_id' => 'sometimes|exists:varian_produk,id',
            'jumlah' => 'required|integer|min:1',
            'alamat_antar' => 'sometimes|string',
            'zona_antar_id' => 'sometimes|exists:zona_antar,id',
        ]);

        // TODO: Implement order creation from customer flow
        // Similar to admin flow but with customer notes

        return back()
            ->with('success', 'Pesanan berhasil dibuat.');
    }

    /**
     * Show a specific order detail.
     *
     * @param  int  $nomor
     * @return View
     */
    public function show($nomor)
    {
        $pesanan = auth('customer')->user()->pesanan()
            ->withTrashed()
            ->where('nomor', $nomor)
            ->firstOrFail();

        return view('pages.customer.order-show', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Cancel an order.
     *
     * @param  int  $nomor
     * @return RedirectResponse
     */
    public function cancel($nomor)
    {
        $pesanan = auth('customer')->user()->pesanan()
            ->where('nomor', $nomor)
            ->firstOrFail();

        $pesanan->status = 'dibatalkan';
        $pesanan->dibatalkan_pana = now();
        $pesanan->save();

        return back()
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }

    /**
     * Request cancellation.
     *
     * @param  int  $nomor
     * @return RedirectResponse
     */
    public function requestCancellation($nomor)
    {
        $pesanan = auth('customer')->user()->pesanan()
            ->where('nomor', $nomor)
            ->firstOrFail();

        return back()
            ->with('success', 'Permintaan pembatalan pesanan telah dikirim ke admin.');
    }
}
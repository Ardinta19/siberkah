<?php

namespace App\Http\Controllers;

use App\Actions\CreateRentalOrder;
use App\Actions\ReleaseOrderHold;
use DomainException;
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
        $pesanan = auth('customer')->user()->pesanan()->latest('dibuat_pada')->paginate(10);

        return view('pelanggan.riwayat', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Store a new order.
     *
     * @return RedirectResponse
     */
    public function store(Request $request, CreateRentalOrder $checkout)
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Checkout belum dibuka.');
        try {
            $order = $checkout->create($request->user('customer'), $request->all());
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        return redirect()->route('pesanan.show', $order->nomor);
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
            ->with('items')
            ->where('nomor', $nomor)
            ->firstOrFail();

        return view('pelanggan.rincian', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Cancel an order.
     *
     * @param  int  $nomor
     * @return RedirectResponse
     */
    public function cancel(Request $request, string $nomor, ReleaseOrderHold $holds)
    {
        try {
            $holds->cancel($request->user('customer'), $nomor);
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
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
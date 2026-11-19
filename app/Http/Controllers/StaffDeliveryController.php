<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Staff Delivery Controller - Delivery/kurir management
 * --------------------------------------------------------
 * guard:staff middleware applied at route level.
 * K-07: Staff Kurir - handover, receive return
 */
class StaffDeliveryController extends BaseController
{
    /**
     * Display the delivery index.
     *
     * @return View
     */
    public function index()
    {
        $pesanan = Pesanan::whereNotNull('tanggal_serah')
            ->latest()
            ->paginate(10);

        return view('pages.staff.kurir.index', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Execute handover.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function handover($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        $pesanan->status = 'diterima';
        $pesanan->tanggal_handover = now();
        $pesanan->save();

        return back()
            ->with('success', 'Handover berhasil dicatat.');
    }

    /**
     * Receive return.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function receiveReturn($id)
    {
        $pesanan = Pesanan::findOrFail($id);

        $pesanan->status = 'dikembalikan';
        $pesanan->tanggal_kembali = now();
        $pesanan->save();

        return back()
            ->with('success', 'Pengembalian berhasil dicatat.');
    }
}
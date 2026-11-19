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
        return view('staf.agenda', [
            'judul' => 'Agenda serah terima',
            'pesanan' => Pesanan::whereIn('status', ['siap', 'sudah_diserahkan'])
                ->orderBy('tanggal_serah')->orderBy('id')->paginate(20),
        ]);
    }

    public function handover(Request $request, \App\Actions\InspectRental $inspection)
    {
        return response()->json($inspection->handle($request, false));
    }

    public function receiveReturn(Request $request, \App\Actions\InspectRental $inspection)
    {
        return response()->json($inspection->handle($request, true));
    }
}

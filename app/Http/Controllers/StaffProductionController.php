<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Staff Production Controller - Production management
 * --------------------------------------------------------
 * guard:staff middleware applied at route level.
 * K-06: Staff Produksi - mark ready, start production
 */
class StaffProductionController extends BaseController
{
    /**
     * Display the production index.
     *
     * @return View
     */
    public function index()
    {
        $produks = Produk::where('aktif', true)->latest()->get();

        return view('pages.staff.produksi.index', [
            'produks' => $produks,
        ]);
    }

    /**
     * Mark production ready.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function markReady(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $produk->status = 'siap';
        $produk->save();

        return back()
            ->with('success', 'Produk berhasil ditandai siap.');
    }

    /**
     * Start production.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function startProduction(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $produk->status = 'diproses';
        $produk->save();

        return back()
            ->with('success', 'Produksi dimulai.');
    }
}
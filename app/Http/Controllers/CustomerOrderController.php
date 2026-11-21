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
    public function index(Request $request)
    {
        $group = $request->query('kelompok');
        $group = in_array($group, ['aktif', 'arsip'], true) ? $group : null;
        $closed = ['selesai', 'dibatalkan', 'kedaluwarsa'];
        $query = $request->user('customer')->pesanan();
        if ($group === 'arsip') {
            $query->whereIn('status', $closed);
        } elseif ($group === 'aktif') {
            $query->whereNotIn('status', $closed);
        }
        $pesanan = $query->latest('dibuat_pada')->orderByDesc('id')->paginate(10)
            ->appends(array_filter(['kelompok' => $group]))->fragment('daftar-pesanan');

        return response()->view('pelanggan.pesanan.index', compact('pesanan', 'group'))
            ->header('Cache-Control', 'private, no-store');
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

    public function bouquet(Request $request, \App\Actions\CreateBouquetOrder $checkout)
    {
        try {
            $order = $checkout->create($request->user('customer'), $request->all());
        } catch (DomainException $e) {
            abort(409, $e->getMessage());
        }
        return response()->json(['nomor' => $order->nomor, 'total' => $order->total]);
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

        return response()->view('pelanggan.pesanan.show', [
            'opsiPerItem' => \Illuminate\Support\Facades\DB::table('opsi_item_pesanan')
                ->whereIn('item_pesanan_id', $pesanan->items->modelKeys())->orderBy('id')->get()
                ->groupBy('item_pesanan_id'),
            'permohonan' => \Illuminate\Support\Facades\DB::table('permohonan_batal')->where('pesanan_id', $pesanan->id)->first(['status','alasan','diajukan_pada','diputuskan_pada','catatan_keputusan']),
            'papan' => \Illuminate\Support\Facades\DB::table('desain_papan')->where('pesanan_id',$pesanan->id)->first(['judul','penerima','pengirim']),
            'riwayat' => \Illuminate\Support\Facades\DB::table('riwayat_status_pesanan')->where('pesanan_id', $pesanan->id)->orderBy('id')->get(['status_baru','dibuat_pada']),
            'pesanan' => $pesanan,
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Cancel an order.
     *
     * @param  int  $nomor
     * @return RedirectResponse
     */
    public function cancel(Request $request, string $nomor, ReleaseOrderHold $holds)
    {
        abort_unless($request->user('customer')->pesanan()->where('nomor', $nomor)->whereExists(
            fn ($q) => $q->selectRaw('1')->from('checkout_bank')->whereColumn('pesanan_id', 'pesanan.id'))->exists(), 404);
        try {
            $holds->cancel($request->user('customer'), $nomor);
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }
}

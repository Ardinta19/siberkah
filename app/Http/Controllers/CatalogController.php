<?php

namespace App\Http\Controllers;

use App\Models\HariReservasi;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Catalog Controller - Public routes for product catalog browsing
 * --------------------------------------------------------
 * Accessible to all visitors (guest:customer middleware applied at route level)
 */
class CatalogController extends BaseController
{
    /**
     * Display the product catalog listing.
     * K-01: Beranda/Katalog - grid 2 kolom mobile, 4 kolom desktop
     *
     * @return View
     */
    public function index(Request $request)
    {
        $query = Produk::where('aktif', true);

        // Filter by kategori
        if ($request->has('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        // Filter by tanggal (ketersediaan)
        if ($request->has('tanggal')) {
            // Check availability for the given date
            $tanggal = $request->tanggal;
            // TODO: Implement availability check usinghari_reservasi
        }

        // Filter by jenis (sewa/jual/custom)
        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();
        $produk = $query->with(['varianProduk' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('urutan')
            ->get();

        return view('katalog.index', [
            'produk' => $produk,
            'kategoris' => $kategoris,
            'filterKategori' => $request->get('kategori'),
            'filterJenis' => $request->get('jenis'),
            'filterTanggal' => $request->get('tanggal'),
        ]);
    }

    /**
     * Show product detail page.
     * K-02: Detail Produk Sewa - galeri foto, varian warna, tanggal, opsi, total
     *
     * @return View
     */
    public function show(string $slug, Request $request)
    {
        $produk = Produk::where('slug', $slug)->where('aktif', true)->firstOrFail();

        // Load relasi
        $varian = $produk->varianProduk()->where('aktif', true)->get();
        $opsiProduk = $produk->opsiProduk()->where('aktif', true)->get();
        $gambarProduk = $produk->gambarProduk()->where('aktif', true)->get();

        // Check availability for specific date if provided
        $tanggal = $request->get('tanggal');
        $tersedia = true;
        if ($tanggal && $produk->jenis === 'sewa') {
            // Check if any unit is already reserved for this date
            $occupiedUnits = HariReservasi::where('tanggal', $tanggal)
                ->whereNotNull('unit_sewa_id')
                ->count();
            $totalUnits = $produk->varianProduk->sum('stok');
            $tersedia = $occupiedUnits < $totalUnits;
        }

        return view('katalog.show', [
            'produk' => $produk,
            'varian' => $varian,
            'opsiProduk' => $opsiProduk,
            'gambarProduk' => $gambarProduk,
            'tersedia' => $tersedia,
            'tanggalRequest' => $tanggal,
        ]);
    }
}

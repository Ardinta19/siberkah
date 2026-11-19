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

        $request->validate(['q' => 'nullable|string|max:150', 'kategori' => 'nullable|integer', 'jenis' => 'nullable|string', 'tanggal' => 'nullable|date_format:Y-m-d']);
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim().'%';
            $query->where(fn ($q) => $q->where('nama', 'like', $term)
                ->orWhereHas('kategori', fn ($category) => $category->where('nama', 'like', $term)));
        }

        // Filter by kategori
        if ($request->has('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        // Filter by tanggal (ketersediaan)
        if ($request->filled('tanggal')) {
            $query->where(fn ($q) => $q->where('jenis', '!=', 'sewa')
                ->orWhere(fn ($rental) => $rental->bisaDisewaPada($request->input('tanggal'))));
        }

        // Filter by jenis (sewa/jual/custom)
        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();
        $produk = $query->with(['kategori', 'varianProduk' => fn ($q) => $q->where('aktif', true)])
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
        $opsiProduk = $produk->opsiProduk()->orderBy('urutan')->get();
        $gambarProduk = $produk->gambarProduk()->orderBy('urutan')->get();

        // Check availability for specific date if provided
        $tanggal = $request->validate(['tanggal' => 'nullable|date_format:Y-m-d'])['tanggal'] ?? null;
        $tersedia = $tanggal && $produk->jenis === 'sewa'
            ? Produk::whereKey($produk->id)->bisaDisewaPada($tanggal)->exists()
            : null;

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

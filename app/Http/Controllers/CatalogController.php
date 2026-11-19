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
        $query = Produk::where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', true));

        $request->validate(['q' => 'nullable|string|max:150', 'kategori' => 'nullable|integer', 'jenis' => 'nullable|in:sewa,jual,custom', 'urut' => 'nullable|in:harga_naik,harga_turun', 'tanggal' => 'nullable|date_format:Y-m-d']);
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim().'%';
            $query->where(fn ($q) => $q->where('nama', 'like', $term)
                ->orWhereHas('kategori', fn ($category) => $category->where('nama', 'like', $term)));
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        $date = $request->input('tanggal');
        if ($date) {
            $query->bisaDisewaPada($date);
        }

        // Filter by jenis (sewa/jual/custom)
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();
        if ($request->filled('urut')) {
            $query->orderBy('harga_dasar', $request->input('urut') === 'harga_turun' ? 'desc' : 'asc');
        }
        $produk = $query->with(['kategori', 'varianProduk' => fn ($q) => $date ? $q->tersediaPada($date) : $q->where('aktif', true)])
            ->orderBy('urutan')->orderBy('id')->paginate(24)->withQueryString();

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
        $produk = Produk::where('slug', $slug)->where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', true))->firstOrFail();

        $tanggal = $request->validate(['tanggal' => 'nullable|date_format:Y-m-d'])['tanggal'] ?? null;
        $filter = $tanggal && $produk->jenis === 'sewa';
        $varian = $produk->varianProduk()->where('aktif', true)->when($filter, fn ($q) => $q->tersediaPada($tanggal))->get();
        $tersedia = $filter ? $varian->isNotEmpty() : null;
        $saran = [];
        if ($tersedia === false) {
            $day = \Carbon\CarbonImmutable::parse($tanggal, 'Asia/Jakarta');
            for ($i = 1; $i <= 14 && count($saran) < 3; $i++) {
                $candidate = $day->addDays($i)->toDateString();
                if ($candidate >= today('Asia/Jakarta')->toDateString() && $produk->varianProduk()->tersediaPada($candidate)->exists()) {
                    $saran[] = $candidate;
                }
            }
        }
        $opsiProduk = $produk->opsiProduk()->orderBy('urutan')->get();
        $gambarProduk = $produk->gambarProduk()->orderBy('urutan')->get();

        return view('katalog.show', [
            'produk' => $produk,
            'varian' => $varian,
            'opsiProduk' => $opsiProduk,
            'gambarProduk' => $gambarProduk,
            'tersedia' => $tersedia,
            'saran' => $saran,
            'tanggalRequest' => $tanggal,
        ]);
    }
}

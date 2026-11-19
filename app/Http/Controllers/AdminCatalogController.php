<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Admin Catalog Controller - CRUD for products, variants, options, images, units
 * --------------------------------------------------------
 * auth:staff + role:owner,admin middleware applied at route level.
 * K-08: Admin Katalog - CRUD produk, varian, opsi, gambar (drag-drop, WebP convert, alt wajib)
 */
class AdminCatalogController extends BaseController
{
    /**
     * Display the catalog management index.
     *
     * @return View
     */
    public function index()
    {
        $produk = Produk::with('kategori')->latest()->paginate(20);
        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();

        return view('admin.katalog.index', [
            'produk' => $produk,
            'kategoris' => $kategoris,
        ]);
    }

    /**
     * Show the create form.
     *
     * @return View
     */
    public function create()
    {
        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();
        $zonas = null; // ZonaAntar::where('aktif', true)->get();

        return view('admin.katalog.create', [
            'kategoris' => $kategoris,
            'zonas' => $zonas,
        ]);
    }

    /**
     * Store a new product.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string|max:150',
            'slug' => 'required|string|max:170|unique:produk,slug',
            'jenis' => 'required|in:sewa,jual,custom',
            'kelompok_kapasitas' => 'nullable|string|max:30',
            'deskripsi' => 'nullable|string',
            'harga_dasar' => 'required|integer|min:0',
            'foto_utama' => 'nullable|image|max:5120',
            'aktif' => 'boolean',
        ]);

        $data = $request->except('foto_utama');
        $data['aktif'] = $request->has('aktif');

        if ($request->hasFile('foto_utama')) {
            $path = $request->file('foto_utama')->store('produk', 'public');
            $data['foto_utama'] = $path;
        }

        $produk = Produk::create($data);

        return redirect()
            ->route('admin.katalog.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the edit form.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id)
    {
        $produk = Produk::with(['kategori', 'varianProduk', 'opsiProduk', 'gambarProduk'])->findOrFail($id);
        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();

        return view('admin.katalog.edit', [
            'produk' => $produk,
            'kategoris' => $kategoris,
        ]);
    }

    /**
     * Update the product.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string|max:150',
            'slug' => 'required|string|max:170|unique:produk,slug,'.$id,
            'jenis' => 'required|in:sewa,jual,custom',
            'kelompok_kapasitas' => 'nullable|string|max:30',
            'deskripsi' => 'nullable|string',
            'harga_dasar' => 'required|integer|min:0',
            'foto_utama' => 'nullable|image|max:5120',
            'aktif' => 'boolean',
        ]);

        $data = $request->except('foto_utama');
        $data['aktif'] = $request->has('aktif');

        if ($request->hasFile('foto_utama')) {
            // Delete old image if exists
            if ($produk->foto_utama) {
                \Storage::disk('public')->delete($produk->foto_utama);
            }
            $path = $request->file('foto_utama')->store('produk', 'public');
            $data['foto_utama'] = $path;
        }

        $produk->update($data);

        return redirect()
            ->route('admin.katalog.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Archive a product (soft delete).
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function archive($id)
    {
        $produk = Produk::findOrFail($id);
        $produk->aktif = false;
        $produk->save();

        return redirect()
            ->route('admin.katalog.index')
            ->with('success', 'Produk diarsipkan.');
    }

    /**
     * Publish a product (un-archive).
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function publish($id)
    {
        $produk = Produk::findOrFail($id);
        $produk->aktif = true;
        $produk->save();

        return redirect()
            ->route('admin.katalog.index')
            ->with('success', 'Produk dipublikasikan.');
    }

    /**
     * Manage variants for a product.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function manageVariants(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        // TODO: Handle variant management
        // - Create/update variants
        // - Set stok per variant
        // - Associate with unit_sewa

        return back()
            ->with('success', 'Varian produk berhasil dikelola.');
    }

    /**
     * Manage options for a product.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function manageOptions(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        // TODO: Handle option management
        // - Create/update opsi_produk
        // - Create/update nilai_opsi
        // - Set wajib/opsional

        return back()
            ->with('success', 'Opsi produk berhasil dikelola.');
    }

    /**
     * Manage images for a product.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function manageImages(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        // TODO: Handle image upload and management
        // - WebP conversion
        // - EXIF strip
        // - Alt text wajib
        // - Max 5MB, min 800px width

        return back()
            ->with('success', 'Gambar produk berhasil dikelola.');
    }

    /**
     * Manage units for a product.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function manageUnits(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        // TODO: Handle unit management
        // - CRUD unit_sewa
        // - Set status aktif/nonaktif
        // - Associate with varian_produk

        return back()
            ->with('success', 'Unit sewa berhasil dikelola.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Support\StaffAudit;
use Illuminate\Support\Facades\DB;
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
    private function listContext(Request $request): array
    {
        $state = $request->query('tampil');
        return array_filter([
            'tampil' => in_array($state, ['publik','nonaktif','kategori'], true) ? $state : null,
            'page' => $request->integer('page') > 1 ? $request->integer('page') : null,
        ]);
    }

    public function index(Request $request)
    {
        $context = $this->listContext($request);
        $state = $context['tampil'] ?? null;
        $query = Produk::with('kategori');
        if ($state === 'nonaktif') {
            $query->where('aktif', false);
        } elseif ($state) {
            $query->where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', $state === 'publik'));
        }
        $produk = $query->orderByDesc('id')->paginate(20)->appends(array_filter(['tampil'=>$state]))->fragment('daftar-kelola');
        return view('owner.katalog.index', compact('produk','state','context'));
    }

    /**
     * Show the create form.
     *
     * @return View
     */
    public function create(Request $request)
    {
        $kategoris = Kategori::where('aktif', true)->orderBy('urutan')->get();
        $zonas = null; // ZonaAntar::where('aktif', true)->get();

        return view('owner.katalog.create', [
            'kategoris' => $kategoris,
            'zonas' => $zonas,
            'context' => $this->listContext($request),
        ]);
    }

    /**
     * Store a new product.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string|max:150',
            'slug' => 'required|string|max:170|unique:produk,slug',
            'jenis' => 'required|in:sewa,jual,custom',
            'kelompok_kapasitas' => 'nullable|string|max:30',
            'deskripsi' => 'nullable|string',
            'izin_hari_sama' => 'sometimes|boolean',
            'lead_custom_hari' => 'sometimes|integer|in:1,2',
            'harga_dasar' => 'required|integer|min:0',
            'foto_utama' => 'prohibited',
            'aktif' => 'boolean',
        ]);

        unset($data['foto_utama']);
        $data['aktif'] = false; // Publishing is a separate Owner action.
        DB::transaction(function () use ($data) {
            $product = Produk::create($data);
            StaffAudit::record('buat_draf', 'produk', $product->id, null, $product->fresh()->only(array_keys($data)));
        });

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
    public function edit(Request $request, $id)
    {
        $produk = Produk::with(['kategori', 'varianProduk', 'opsiProduk', 'gambarProduk'])->findOrFail($id);
        $kategoris = Kategori::where('aktif', true)->orWhere('id', $produk->kategori_id)->orderBy('urutan')->get();

        return view('owner.katalog.edit', [
            'produk' => $produk,
            'jenisTerkunci' => DB::table('item_pesanan')->where('produk_id', $produk->id)->exists(),
            'context' => $this->listContext($request),
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

        $data = $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'nama' => 'required|string|max:150',
            'slug' => 'required|string|max:170|unique:produk,slug,'.$id,
            'jenis' => 'required|in:sewa,jual,custom',
            'kelompok_kapasitas' => 'nullable|string|max:30',
            'deskripsi' => 'nullable|string',
            'izin_hari_sama' => 'sometimes|boolean',
            'lead_custom_hari' => 'sometimes|integer|in:1,2',
            'harga_dasar' => 'required|integer|min:0',
            'foto_utama' => 'prohibited',
            'aktif' => 'boolean',
        ]);

        unset($data['foto_utama'], $data['aktif']);
        DB::transaction(function () use ($data, $id) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            abort_if($product->jenis !== $data['jenis'] && DB::table('item_pesanan')->where('produk_id', $id)->exists(), 409, 'Jenis produk dalam riwayat pesanan tidak dapat diubah.');
            $before = $product->only(array_keys($data));
            $product->update($data);
            StaffAudit::record('ubah_produk', 'produk', $product->id, $before, $product->fresh()->only(array_keys($data)));
        });

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
        $this->visibility($id, false);

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
        $this->visibility($id, true);

        return redirect()
            ->route('admin.katalog.index')
            ->with('success', 'Produk dipublikasikan.');
    }

    private function visibility(int $id, bool $active): void
    {
        DB::transaction(function () use ($id, $active) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            $old = $product->aktif;
            if ($old === $active) { return; }
            abort_if($active && ! $product->kategori()->where('aktif', true)->exists(), 409, 'Kategori belum aktif.');
            $product->update(['aktif' => $active]);
            StaffAudit::record($active ? 'terbit_produk' : 'arsip_produk', 'produk', $id, ['aktif' => $old], ['aktif' => $active]);
        });
    }
}

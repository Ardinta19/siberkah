<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Support\StaffAudit;
use Illuminate\Support\Facades\DB;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use App\Support\OwnerCatalogContext;
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
    public function index(Request $request)
    {
        $context = OwnerCatalogContext::from($request);
        $state = $context['tampil'] ?? null;
        $query = Produk::with('kategori');
        if (isset($context['q'])) {
            $term = strtr($context['q'], ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("produk.nama LIKE ? ESCAPE '!'", ['%'.$term.'%']);
        }
        if ($state === 'nonaktif') {
            $query->where('aktif', false);
        } elseif ($state) {
            $query->where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', $state === 'publik'));
        }
        $produk = $query->orderByDesc('id')->paginate(20)->appends(array_diff_key($context, ['page'=>true]))->fragment('daftar-kelola');
        if ($produk->currentPage() > $produk->lastPage()) {
            return redirect($produk->url($produk->lastPage()));
        }
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
            'context' => OwnerCatalogContext::from($request),
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
            'kategori_id' => ['required', \Illuminate\Validation\Rule::exists('kategori','id')->where('aktif', true)],
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
            ->route('admin.katalog.index', OwnerCatalogContext::from($request))
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the edit form.
     *
     * @param  int  $id
     * @return View
     */
    private function editorStamp(Produk $product): string
    {
        // Ignore unrelated gallery edits; include visibility because a publish changes who sees the edit.
        return hash('sha256', json_encode($product->only(['id','nama','slug','kategori_id','jenis','harga_dasar',
            'deskripsi','lead_custom_hari','izin_hari_sama','kelompok_kapasitas','aktif']), JSON_THROW_ON_ERROR));
    }

    public function edit(Request $request, $id)
    {
        $produk = Produk::with(['kategori', 'varianProduk', 'opsiProduk', 'gambarProduk'])->findOrFail($id);
        $kategoris = Kategori::where('aktif', true)->orWhere('id', $produk->kategori_id)->orderBy('urutan')->get();

        return view('owner.katalog.edit', [
            'produk' => $produk,
            'editorStamp' => $this->editorStamp($produk),
            'jenisTerkunci' => DB::table('item_pesanan')->where('produk_id', $produk->id)->exists(),
            'context' => OwnerCatalogContext::from($request),
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
        $saved = DB::transaction(function () use ($data, $id, $request) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            if ($request->has('versi_editor') && $request->input('versi_editor') !== $this->editorStamp($product)) {
                return false;
            }
            if ((int)$data['kategori_id'] !== (int)$product->kategori_id && !Kategori::whereKey($data['kategori_id'])->where('aktif',true)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['kategori_id'=>'Pilih kategori aktif untuk memindahkan produk.']);
            }
            abort_if($product->jenis !== $data['jenis'] && DB::table('item_pesanan')->where('produk_id', $id)->exists(), 409, 'Jenis produk dalam riwayat pesanan tidak dapat diubah.');
            $before = $product->only(array_keys($data));
            $product->fill($data);
            if (!$product->isDirty()) { return true; }
            $product->save();
            StaffAudit::record('ubah_produk', 'produk', $product->id, $before, $product->fresh()->only(array_keys($data)));
            return true;
        });
        if (!$saved) {
            abort_if($request->expectsJson(), 409, 'Produk sudah berubah.');
            return redirect()->route('admin.katalog.edit',['id'=>$id]+OwnerCatalogContext::from($request))
                ->with('editor_conflict', 'Produk berubah sejak editor dibuka. Isian lama tidak disimpan. Tinjau data terbaru di bawah sebelum menyunting kembali.');
        }
        return redirect()->route('admin.katalog.index', OwnerCatalogContext::from($request))->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Archive a product (soft delete).
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function archive(Request $request, $id)
    {
        $this->visibility($id, false);

        return redirect()
            ->route('admin.katalog.index', OwnerCatalogContext::from($request))
            ->with('success', 'Produk diarsipkan.');
    }

    /**
     * Publish a product (un-archive).
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function publish(Request $request, $id)
    {
        $this->visibility($id, true);

        return redirect()
            ->route('admin.katalog.index', OwnerCatalogContext::from($request))
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

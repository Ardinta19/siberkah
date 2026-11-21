<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Support\OwnerCatalogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CatalogMediaController extends Controller
{
    private function images(int $id)
    {
        return DB::table('gambar_produk')->where('produk_id', $id)->orderBy('urutan')->orderBy('id');
    }

    private function orderStamp($photos): string
    {
        return hash('sha256', $photos->pluck('id')->implode(','));
    }

    private function staleGallery(Request $request, int $id)
    {
        $message = 'Galeri berubah sejak halaman ini dibuka. Perubahan Anda tidak diterapkan. Periksa susunan terbaru sebelum melanjutkan.';
        if ($request->expectsJson() || $request->input('asal') !== 'galeri') {
            abort(409, $message);
        }
        return redirect()->route('admin.katalog.gallery', ['id'=>$id]+OwnerCatalogContext::from($request))->with('galeri_konflik',$message);
    }

    public function manage(Request $request, int $id)
    {
        $product = Produk::with('varianProduk')->findOrFail($id);
        $photos = $this->images($id)->get();
        foreach ($photos as $photo) { $photo->detail_stamp = $this->detailStamp($photo); }
        return response()->view('owner.katalog.gallery', ['product'=>$product, 'photos'=>$photos, 'stamp'=>$this->orderStamp($photos),
            'context'=>OwnerCatalogContext::from($request)])->header('Cache-Control','private, no-store');
    }

    private function finished(Request $request, int $id, string $message, $apiResponse)
    {
        return !$request->expectsJson() && $request->input('asal') === 'galeri'
            ? redirect()->route('admin.katalog.gallery', ['id'=>$id]+OwnerCatalogContext::from($request))->with('success',$message)
            : $apiResponse;
    }

    public function index(int $id)
    {
        Produk::findOrFail($id);
        return response()->json($this->images($id)->get())->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, int $id)
    {
        $data = $request->validate(['foto' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=800,max_width=8000,max_height=8000',
            'teks_alternatif' => 'required|string|max:255', 'varian_produk_id' => 'nullable|integer|min:1']);
        Produk::findOrFail($id);
        $file = $data['foto'];
        [$width, $height] = getimagesize($file->getRealPath());
        abort_if($width * $height > 12000000, 422, 'Maksimum 12 megapiksel.');
        abort_unless(function_exists('imagewebp') && (imagetypes() & IMG_WEBP), 503, 'PHP GD dengan WebP diperlukan untuk unggahan katalog.');
        $image = @imagecreatefromstring($file->getContent());
        if (! $image) {
            throw ValidationException::withMessages(['foto' => 'Berkas gambar tidak dapat dibaca.']);
        }
        // Decode/re-encode pixels only: never store the source or copy EXIF, XMP, or original filename.
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        try {
            $ok = imagewebp($image, null, 82);
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            unset($image);
        }
        abort_unless($ok && strlen($bytes) > 12 && substr($bytes, 8, 4) === 'WEBP', 503, 'Konversi WebP gagal.');
        $path = 'produk/galeri/'.Str::uuid().'.webp';
        try {
            $result = DB::transaction(function () use ($id, $path, $data, $bytes, $request) {
                $product = Produk::lockForUpdate()->findOrFail($id);
                $variant = $data['varian_produk_id'] ?? null;
                if ($variant !== null) {
                    abort_unless($product->varianProduk()->whereKey($variant)->exists(), 422, 'Varian bukan milik produk ini.');
                }
                $position = (int) DB::table('gambar_produk')->where('produk_id', $id)->max('urutan') + 1;
                abort_if($position > 50, 422, 'Maksimum 50 foto per produk.');
                abort_unless(Storage::disk('public')->put($path, $bytes), 503, 'Penyimpanan foto gagal.');
                $imageId = DB::table('gambar_produk')->insertGetId(['produk_id' => $id, 'varian_produk_id' => $variant, 'jalur_berkas' => $path,
                    'urutan' => $position, 'teks_alternatif' => $data['teks_alternatif'], 'dibuat_pada' => now()]);
                if (! $product->foto_utama) {
                    $product->update(['foto_utama' => $path]);
                }
                $this->audit($request, $id, 'unggah_gambar', ['gambar_id' => $imageId, 'urutan' => $position]);
                return $imageId;
            });
        } catch (Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }
        return $this->finished($request,$id,'Foto berhasil ditambahkan.', response()->json(['id' => $result, 'jalur_berkas' => $path], 201));
    }

    private function detailStamp(object $photo): string
    {
        return hash('sha256', json_encode([$photo->id, $photo->teks_alternatif, $photo->varian_produk_id], JSON_THROW_ON_ERROR));
    }

    public function updateDetails(Request $request, int $id, int $foto)
    {
        $data = $request->validate(['detail.teks'=>'required|string|max:255', 'detail.varian'=>'nullable|integer|min:1',
            'versi_detail'=>'required|string|size:64']);
        $saved = DB::transaction(function () use ($request, $id, $foto, $data) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            $row = $this->images($id)->where('id',$foto)->first();
            if (!$row || $this->detailStamp($row) !== $data['versi_detail']) { return false; }
            $variant = $data['detail']['varian'] ?? null;
            if ($variant !== null && !$product->varianProduk()->whereKey($variant)->exists()) {
                throw ValidationException::withMessages(['detail.varian'=>'Pilih varian milik produk ini.']);
            }
            if ($row->teks_alternatif === $data['detail']['teks'] && (string)$row->varian_produk_id === (string)$variant) { return true; }
            DB::table('gambar_produk')->where('id',$foto)->update(['teks_alternatif'=>$data['detail']['teks'],'varian_produk_id'=>$variant]);
            $this->audit($request,$id,'ubah_detail_gambar',['gambar_id'=>$foto,'varian_produk_id'=>$variant]);
            return true;
        }, 3);
        if (!$saved) { return $this->staleGallery($request,$id); }
        return $this->finished($request,$id,'Detail foto telah disimpan.',response()->noContent());
    }

    public function reorder(Request $request, int $id)
    {
        $ids = $request->validate(['urutan' => 'required|array|list|min:1|max:50', 'urutan.*' => 'required|integer|distinct|min:1'])['urutan'];
        $saved = DB::transaction(function () use ($request, $id, $ids) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            $rows = $this->images($id)->get()->keyBy('id');
            if (($request->has('versi_galeri') || $request->input('asal') === 'galeri') && $request->input('versi_galeri') !== $this->orderStamp($rows)) {
                return false;
            }
            abort_unless($rows->count() === count($ids) && $rows->has($ids), 409, 'Kirim daftar lengkap foto produk ini.');
            foreach ($ids as $index => $image) {
                DB::table('gambar_produk')->where('id', $image)->update(['urutan' => $index + 1]);
            }
            $product->update(['foto_utama' => $rows[$ids[0]]->jalur_berkas]);
            $this->audit($request, $id, 'urut_gambar', ['urutan' => $ids]);
            return true;
        }, 3);
        if (!$saved) { return $this->staleGallery($request, $id); }
        return $this->finished($request,$id,'Urutan dan foto utama telah disimpan.',response()->noContent());
    }

    public function remove(Request $request, int $id, int $foto)
    {
        $path = DB::transaction(function () use ($request, $id, $foto) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            if (($request->has('versi_galeri') || $request->input('asal') === 'galeri') && $request->input('versi_galeri') !== $this->orderStamp($this->images($id)->get(['id']))) {
                return null;
            }
            $row = $this->images($id)->where('id', $foto)->first();
            abort_unless($row, 404);
            DB::table('gambar_produk')->where('id', $foto)->delete();
            foreach ($this->images($id)->pluck('id') as $index => $image) {
                DB::table('gambar_produk')->where('id', $image)->update(['urutan' => $index + 1]);
            }
            if ($product->foto_utama === $row->jalur_berkas) {
                $product->update(['foto_utama' => $this->images($id)->value('jalur_berkas')]);
            }
            $this->audit($request, $id, 'hapus_gambar', ['gambar_id' => $foto]);
            return $row->jalur_berkas;
        }, 3);
        if ($path === null) { return $this->staleGallery($request, $id); }
        // Only unlink unique files created by this workflow, not arbitrary legacy paths.
        if (preg_match('~^produk/galeri/[a-f0-9-]{36}\.webp$~D', $path)) {
            abort_unless(Storage::disk('public')->delete($path), 503, 'Referensi terhapus, berkas perlu dibersihkan operator.');
        }
        return $this->finished($request,$id,'Foto telah dihapus.',response()->noContent());
    }

    private function audit(Request $request, int $id, string $action, array $details): void
    {
        DB::table('log_aktivitas')->insert(['pengguna_id' => $request->user('staff')->id, 'aksi' => $action, 'subjek_tabel' => 'produk',
            'subjek_id' => $id, 'sesudah' => json_encode($details, JSON_THROW_ON_ERROR), 'dibuat_pada' => now()]);
    }
}

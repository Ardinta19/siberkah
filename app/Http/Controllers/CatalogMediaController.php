<?php

namespace App\Http\Controllers;

use App\Models\Produk;
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

    private function context(Request $request): array
    {
        return array_filter(['page'=>$request->integer('page') > 1 ? $request->integer('page') : null,
            'tampil'=>in_array($request->query('tampil'), ['publik','nonaktif','kategori'], true) ? $request->query('tampil') : null]);
    }

    public function manage(Request $request, int $id)
    {
        $product = Produk::with('varianProduk')->findOrFail($id);
        return response()->view('owner.katalog.gallery', ['product'=>$product, 'photos'=>$this->images($id)->get(),
            'context'=>$this->context($request)])->header('Cache-Control','private, no-store');
    }

    private function finished(Request $request, int $id, string $message, $apiResponse)
    {
        return !$request->expectsJson() && $request->input('asal') === 'galeri'
            ? redirect()->route('admin.katalog.gallery', ['id'=>$id]+$this->context($request))->with('success',$message)
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

    public function reorder(Request $request, int $id)
    {
        $ids = $request->validate(['urutan' => 'required|array|list|min:1|max:50', 'urutan.*' => 'required|integer|distinct|min:1'])['urutan'];
        DB::transaction(function () use ($request, $id, $ids) {
            $product = Produk::lockForUpdate()->findOrFail($id);
            $rows = $this->images($id)->get()->keyBy('id');
            abort_unless($rows->count() === count($ids) && $rows->has($ids), 409, 'Kirim daftar lengkap foto produk ini.');
            foreach ($ids as $index => $image) {
                DB::table('gambar_produk')->where('id', $image)->update(['urutan' => $index + 1]);
            }
            $product->update(['foto_utama' => $rows[$ids[0]]->jalur_berkas]);
            $this->audit($request, $id, 'urut_gambar', ['urutan' => $ids]);
        }, 3);
        return $this->finished($request,$id,'Urutan dan foto utama telah disimpan.',response()->noContent());
    }

    public function remove(Request $request, int $id, int $foto)
    {
        $path = DB::transaction(function () use ($request, $id, $foto) {
            $product = Produk::lockForUpdate()->findOrFail($id);
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

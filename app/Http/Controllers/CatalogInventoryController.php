<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use App\Support\OwnerCatalogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CatalogInventoryController extends Controller
{
    private function variantStamp(object $row, string $kind): string
    {
        return hash('sha256', json_encode([$kind,$row->id,$row->nama,$row->harga,$row->stok,$row->aktif], JSON_THROW_ON_ERROR));
    }

    public function variants(Request $request, int $id)
    {
        $product = DB::table('produk')->where('id',$id)->first();
        abort_unless($product,404);
        $variants = DB::table('varian_produk')->where('produk_id',$id)->orderBy('nama')->orderBy('id')->get();
        $selected = $request->has('edit') ? $variants->firstWhere('id',$request->integer('edit')) : null;
        abort_if($request->has('edit') && !$selected,404);
        return response()->view('owner.katalog.variants', ['product'=>$product,'variants'=>$variants,'selected'=>$selected,
            'stamp'=>$selected ? $this->variantStamp($selected,$product->jenis) : null,'context'=>OwnerCatalogContext::from($request)])->header('Cache-Control','private, no-store');
    }

    public function variant(Request $request, int $id)
    {
        $data = $request->validate(['id' => 'sometimes|integer|min:1', 'nama' => 'required|string|max:80', 'harga' => 'required|integer|between:0,2147482648',
            'stok' => 'sometimes|integer|between:0,65535', 'aktif' => 'required|boolean']);
        $result = DB::transaction(function () use ($id, $data, $request) {
            $product = $this->product($id);
            if (isset($data['id']) && ($request->input('asal') === 'varian' || $request->has('versi_varian'))) {
                $row = DB::table('varian_produk')->where('produk_id',$id)->where('id',$data['id'])->lockForUpdate()->first();
                abort_unless($row,404);
                if ($this->variantStamp($row,$product->jenis) !== $request->input('versi_varian')) { return null; }
            }
            abort_if(isset($data['stok']) && $product->jenis !== 'jual', 422, 'Stok angka hanya untuk produk siap jual.');
            return $this->save($request, 'varian_produk', ['produk_id' => $id], $data);
        }, 3);
        $url = route('admin.katalog.variants',['id'=>$id]+OwnerCatalogContext::from($request));
        if ($result === null) {
            abort_if($request->expectsJson(),409,'Data varian sudah berubah.');
            return redirect($url)->with('variant_warning','Varian atau stok telah berubah. Isian lama tidak diterapkan. Periksa data terbaru sebelum mengedit kembali.');
        }
        return !$request->expectsJson() && $request->input('asal') === 'varian'
            ? redirect($url.'#varian-'.$result['id'])->with('success','Varian berhasil disimpan.') : $result;
    }

    public function option(Request $request, int $id)
    {
        $data = $request->validate(['id' => 'sometimes|integer|min:1', 'nama' => 'required|string|max:80', 'tipe' => 'required|in:pilihan,teks,satuan',
            'wajib' => 'required|boolean', 'urutan' => 'sometimes|integer|between:0,65535', 'nilai' => 'sometimes|array|list|max:50',
            'nilai.*' => 'array:id,label,harga_tambahan,aktif', 'nilai.*.id' => 'sometimes|integer|min:1|distinct', 'nilai.*.label' => 'required|string|max:80|distinct',
            'nilai.*.harga_tambahan' => 'required|integer|between:0,2147482648', 'nilai.*.aktif' => 'required|boolean']);
        return DB::transaction(function () use ($request, $id, $data) {
            $this->product($id);
            $values = $data['nilai'] ?? [];
            unset($data['nilai']);
            $option = $this->save($request, 'opsi_produk', ['produk_id' => $id], $data);
            foreach ($values as $position => $value) {
                $this->save($request, 'nilai_opsi', ['opsi_produk_id' => $option['id']], $value + ['urutan' => $position]);
            }
            return $option;
        }, 3);
    }

    public function unit(Request $request, int $id)
    {
        $data = $request->validate(['id' => 'sometimes|integer|min:1', 'varian_produk_id' => 'required|integer|min:1', 'kode' => 'required|string|max:20',
            'status' => 'required|in:aktif,perawatan,rusak,pensiun', 'catatan' => 'nullable|string|max:200']);
        try {
            return DB::transaction(function () use ($request, $data, $id) {
                abort_unless($this->product($id)->jenis === 'sewa', 422);
                $variant = DB::table('varian_produk')->where('produk_id', $id)->where('id', $data['varian_produk_id'])->lockForUpdate()->first();
                abort_unless($variant, 404);
                if (isset($data['id'])) {
                    $unit = DB::table('unit_sewa')->where('id', $data['id'])->where('varian_produk_id', $variant->id)->lockForUpdate()->first();
                    abort_unless($unit, 404);
                    abort_if($unit->kode !== $data['kode'], 409, 'Kode fisik tidak dapat diganti.');
                    if ($unit->status !== $data['status']) {
                        abort_if(DB::table('penguasaan_unit')->where('unit_sewa_id', $unit->id)->exists() || DB::table('hari_reservasi as h')
                            ->join('pesanan as p', 'p.id', '=', 'h.pesanan_id')->where('h.unit_sewa_id', $unit->id)
                            ->whereIn('p.status', ['menunggu_pembayaran','dikonfirmasi','sedang_diproduksi','siap','sudah_diserahkan'])->exists(), 409, 'Unit masih terikat pada pesanan.');
                    }
                }
                return $this->save($request, 'unit_sewa', ['varian_produk_id' => $variant->id], $data);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Kode unit sudah digunakan.');
        }
    }

    private function product(int $id): object
    {
        $product = DB::table('produk')->where('id', $id)->lockForUpdate()->first();
        abort_unless($product, 404);
        return $product;
    }

    /** Called inside the product transaction; child IDs never cross their parent boundary. */
    private function save(Request $request, string $table, array $parent, array $data): array
    {
        $id = $data['id'] ?? null;
        unset($data['id']);
        $now = now();
        $before = null;
        if ($id) {
            $query = DB::table($table)->where($parent)->where('id', $id);
            $before = $query->lockForUpdate()->first();
            abort_unless($before, 404);
            $query->update($data + ['diubah_pada' => $now]);
        } else {
            $id = DB::table($table)->insertGetId($parent + $data + ['dibuat_pada' => $now, 'diubah_pada' => $now]);
        }
        DB::table('log_aktivitas')->insert(['pengguna_id' => $request->user('staff')->id, 'subjek_tabel' => $table, 'subjek_id' => $id,
            'aksi' => $before ? 'ubah_katalog' : 'tambah_katalog', 'sebelum' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'sesudah' => json_encode($parent + $data, JSON_THROW_ON_ERROR), 'dibuat_pada' => $now]);
        return ['id' => (int) $id];
    }
}

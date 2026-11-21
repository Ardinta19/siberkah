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

    private function choiceValues(int $optionId)
    {
        return DB::table('nilai_opsi')->where('opsi_produk_id',$optionId)->orderBy('urutan')->orderBy('id')->get();
    }

    public function options(Request $request, int $id)
    {
        $product = DB::table('produk')->where('id',$id)->whereIn('jenis',['jual','custom'])->first();
        abort_unless($product,404);
        $options = DB::table('opsi_produk')->where('produk_id',$id)->orderBy('urutan')->orderBy('id')->get();
        $selected = $request->has('edit') ? $options->firstWhere('id',$request->integer('edit')) : null;
        abort_if($request->has('edit') && !$selected,404);
        $values = $selected ? $this->choiceValues($selected->id) : collect();
        $stamp = hash('sha256',json_encode([$selected,$values],JSON_THROW_ON_ERROR));
        return response()->view('owner.katalog.options',compact('product','options','selected','values','stamp')+
            ['context'=>OwnerCatalogContext::from($request)])->header('Cache-Control','private, no-store');
    }

    public function option(Request $request, int $id)
    {
        $editor = !$request->expectsJson() && $request->input('asal') === 'opsi';
        if ($editor && is_array($request->input('baru')) && filled($request->input('baru.label'))) {
            $values = $request->input('nilai',[]);
            if (is_array($values)) { $values[] = $request->input('baru'); $request->merge(['nilai'=>$values]); }
        }
        $data = $request->validate(['id' => 'sometimes|integer|min:1', 'nama' => 'required|string|max:80', 'tipe' => 'required|in:pilihan,teks,satuan',
            'wajib' => 'required|boolean', 'urutan' => 'sometimes|integer|between:0,65535', 'nilai' => 'sometimes|array|list|max:50',
            'nilai.*' => 'array:id,label,harga_tambahan,aktif', 'nilai.*.id' => 'sometimes|integer|min:1|distinct', 'nilai.*.label' => 'required|string|max:80|distinct',
            'nilai.*.harga_tambahan' => 'required|integer|between:0,2147482648', 'nilai.*.aktif' => 'required|boolean']);
        $result = DB::transaction(function () use ($request, $id, $data, $editor) {
            $product = $this->product($id);
            if ($editor) {
                abort_if($product->jenis==='sewa',422);
                if (isset($data['id'])) {
                    $row=DB::table('opsi_produk')->where('produk_id',$id)->where('id',$data['id'])->first();
                    abort_unless($row,404);
                    if (hash('sha256',json_encode([$row,$this->choiceValues($row->id)],JSON_THROW_ON_ERROR)) !== $request->input('versi_opsi')) { return null; }
                    $data['tipe']=$row->tipe;
                } elseif (DB::table('opsi_produk')->where('produk_id',$id)->count()>=20) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['nama'=>'Maksimal 20 opsi per produk.']);
                }
                if ($data['tipe']==='teks' && !empty($data['nilai'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['nilai'=>'Opsi teks tidak memakai pilihan berharga.']);
                }
                if ($data['tipe']!=='teks' && $data['wajib'] && !collect($data['nilai'] ?? [])->contains(fn ($v)=>(bool)$v['aktif'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['nilai'=>'Opsi wajib memerlukan minimal satu pilihan aktif.']);
                }
            }
            $values = $data['nilai'] ?? [];
            unset($data['nilai']);
            $option = $this->save($request, 'opsi_produk', ['produk_id' => $id], $data);
            foreach ($values as $position => $value) {
                $this->save($request, 'nilai_opsi', ['opsi_produk_id' => $option['id']], $value + ['urutan' => $position]);
            }
            return $option;
        }, 3);
        if (!$editor) { return $result; }
        $params = ['id'=>$id,'edit'=>$result['id'] ?? $data['id']]+OwnerCatalogContext::from($request);
        return redirect()->route('admin.katalog.options',$params)->with($result ? 'success' : 'option_warning',
            $result ? 'Opsi checkout tersimpan.' : 'Opsi sudah berubah. Periksa data terbaru sebelum menyunting kembali.');
    }

    private const UNIT_STATES = ['aktif','perawatan','rusak','pensiun'];

    private function unitFilters(Request $request): array
    {
        $query = $request->query();
        $code = is_string($query['cari_kode'] ?? null) ? mb_substr(trim($query['cari_kode']),0,20) : '';
        $state = $query['status_unit'] ?? null;
        return array_filter(['cari_kode'=>$code !== '' ? $code : null,
            'status_unit'=>in_array($state,self::UNIT_STATES,true) ? $state : null], static fn ($v) => $v !== null);
    }

    private function unitStamp(object $unit): string
    {
        return hash('sha256', json_encode([$unit->id, $unit->varian_produk_id, $unit->kode, $unit->status, $unit->catatan], JSON_THROW_ON_ERROR));
    }

    public function inventory(Request $request)
    {
        $context = OwnerCatalogContext::from($request);
        $query = DB::table('produk')->where('jenis','sewa');
        if (isset($context['q'])) {
            $term = strtr($context['q'],['!'=>'!!','%'=>'!%','_'=>'!_']);
            $query->whereRaw("nama LIKE ? ESCAPE '!'",['%'.$term.'%']);
        }
        $products = $query->orderBy('nama')->orderBy('id')->paginate(20,['id','nama','aktif'])->appends(array_intersect_key($context,['q'=>true]));
        return response()->view('admin.inventaris',compact('products','context'))->header('Cache-Control','private, no-store');
    }

    public function units(Request $request, int $id)
    {
        $product = DB::table('produk')->where('id', $id)->where('jenis', 'sewa')->first();
        abort_unless($product, 404);
        $variants = DB::table('varian_produk')->where('produk_id', $id)->orderBy('nama')->get();
        $query = DB::table('unit_sewa as u')->join('varian_produk as v','v.id','=','u.varian_produk_id')
            ->where('v.produk_id',$id)->select('u.*','v.nama as varian');
        $selected = $request->has('edit') ? (clone $query)->where('u.id',$request->integer('edit'))->first() : null;
        abort_if($request->has('edit') && !$selected,404);
        $context = OwnerCatalogContext::from($request);
        $filters = $this->unitFilters($request);
        if (isset($filters['cari_kode'])) {
            $term = strtr($filters['cari_kode'],['!'=>'!!','%'=>'!%','_'=>'!_']);
            $query->whereRaw("u.kode LIKE ? ESCAPE '!'", ['%'.$term.'%']);
        }
        if (isset($filters['status_unit'])) { $query->where('u.status',$filters['status_unit']); }
        $units = $query->orderBy('u.kode')->paginate(20,['*'],'unit_page')->appends($context+$filters)->fragment('daftar-unit');
        if ($units->currentPage() > $units->lastPage() && !$selected) { return redirect($units->url($units->lastPage())); }
        $unitContext = $context + $filters + ($units->currentPage()>1 ? ['unit_page'=>$units->currentPage()] : []);
        return response()->view('owner.katalog.units', compact('product','variants','selected','units','context','unitContext','filters') + ['states'=>self::UNIT_STATES] +
            ['stamp'=>$selected ? $this->unitStamp($selected) : null])->header('Cache-Control','private, no-store');
    }

    public function unit(Request $request, int $id)
    {
        $data = $request->validate(['id' => 'sometimes|integer|min:1', 'varian_produk_id' => 'required|integer|min:1', 'kode' => 'required|string|max:20',
            'status' => 'required|in:aktif,perawatan,rusak,pensiun', 'catatan' => 'nullable|string|max:200']);
        try {
            $result = DB::transaction(function () use ($request, $data, $id) {
                abort_unless($this->product($id)->jenis === 'sewa', 422);
                $variant = DB::table('varian_produk')->where('produk_id', $id)->where('id', $data['varian_produk_id'])->lockForUpdate()->first();
                abort_unless($variant, 404);
                if (isset($data['id'])) {
                    $unit = DB::table('unit_sewa')->where('id', $data['id'])->where('varian_produk_id', $variant->id)->lockForUpdate()->first();
                    abort_unless($unit, 404);
                    if (($request->input('asal') === 'unit' || $request->has('versi_unit')) && $request->input('versi_unit') !== $this->unitStamp($unit)) {
                        return ['conflict'=>'Unit telah berubah. Periksa status dan catatan terbaru sebelum menyunting kembali.'];
                    }
                    abort_if($unit->kode !== $data['kode'], 409, 'Kode fisik tidak dapat diganti.');
                    if ($unit->status !== $data['status']) {
                        if (DB::table('penguasaan_unit')->where('unit_sewa_id', $unit->id)->exists() || DB::table('hari_reservasi as h')
                            ->join('pesanan as p', 'p.id', '=', 'h.pesanan_id')->where('h.unit_sewa_id', $unit->id)
                            ->whereIn('p.status', ['menunggu_pembayaran','dikonfirmasi','sedang_diproduksi','siap','sudah_diserahkan'])->exists()) { return ['conflict'=>'Unit masih terikat pada pesanan. Status tidak diubah.']; }
                    }
                }
                return $this->save($request, 'unit_sewa', ['varian_produk_id' => $variant->id], $data);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            $result = ['conflict'=>'Kode unit sudah digunakan. Pilih kode fisik lain.'];
        }
        $html = !$request->expectsJson() && $request->input('asal') === 'unit';
        $context = OwnerCatalogContext::from($request) + $this->unitFilters($request);
        if ($request->integer('unit_page') > 1) { $context['unit_page'] = $request->integer('unit_page'); }
        $route = $request->routeIs('admin.inventaris.save') || !$request->user('staff')->isOwner() ? 'admin.inventaris.units' : 'admin.katalog.units';
        if (isset($result['conflict'])) {
            abort_unless($html,409,$result['conflict']);
            return redirect()->route($route,['id'=>$id]+$context+(isset($data['id']) ? ['edit'=>$data['id']] : []))->with('unit_warning',$result['conflict']);
        }
        return $html ? redirect()->route($route,['id'=>$id,'edit'=>$result['id']]+$context)->with('success','Unit sewa berhasil disimpan.') : $result;
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

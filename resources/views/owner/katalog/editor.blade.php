@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<form data-pending="Menyimpan produk…" method="post" action="{{ isset($produk) ? route('admin.katalog.update',$produk->id) : route('admin.katalog.store') }}" class="grid max-w-lg gap-3 p-4">@csrf
@isset($produk) @method('put') @endisset
<label>Nama <input required maxlength="150" name="nama" value="{{ old('nama',$produk->nama ?? '') }}"></label>
<label>Slug <input required maxlength="170" name="slug" value="{{ old('slug',$produk->slug ?? '') }}"></label>
<label>Kategori <select required name="kategori_id">@foreach($kategoris as $k)<option value="{{ $k->id }}" @selected(old('kategori_id',$produk->kategori_id ?? null)==$k->id)>{{ $k->nama }}{{ $k->aktif ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
<label>Jenis <select name="jenis">@foreach(['jual','sewa','custom'] as $j)<option @selected(old('jenis',$produk->jenis ?? '')===$j)>{{ $j }}</option>@endforeach</select></label>
<label>Harga dasar <input type="number" required min="0" name="harga_dasar" value="{{ old('harga_dasar',$produk->harga_dasar ?? 0) }}"></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi',$produk->deskripsi ?? '') }}</textarea></label>
<label>Lead custom (hari) <select name="lead_custom_hari">@foreach([1,2] as $v)<option @selected(old('lead_custom_hari',$produk->lead_custom_hari ?? 1)==$v)>{{ $v }}</option>@endforeach</select></label>
<input type="hidden" name="izin_hari_sama" value="0"><label><input type="checkbox" name="izin_hari_sama" value="1" @checked(old('izin_hari_sama',$produk->izin_hari_sama ?? false))> Izinkan hari yang sama</label>
<button>{{ isset($produk) ? 'Simpan perubahan' : 'Simpan sebagai draf' }}</button></form>

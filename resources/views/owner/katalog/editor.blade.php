@if(session('editor_conflict'))<p class="bk-notice" role="alert">{{ session('editor_conflict') }}</p>@endif
@if($errors->any())<div class="bk-notice" role="alert"><strong>Produk belum disimpan.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<form data-product-editor data-new="{{ isset($produk) ? 0 : 1 }}" data-pending="Menyimpan produk…" method="post" action="{{ isset($produk) ? route('admin.katalog.update',['id'=>$produk->id]+$context) : route('admin.katalog.store') }}" class="bk-product-editor grid max-w-lg gap-3 p-4">@csrf
@isset($produk) @method('put')<input type="hidden" name="versi_editor" value="{{ old('versi_editor',$editorStamp) }}"> @endisset
<label>Nama <input required maxlength="150" name="nama" value="{{ old('nama',$produk->nama ?? '') }}"></label>
<label>Slug (alamat produk) <input aria-describedby="slug-help" required maxlength="170" name="slug" value="{{ old('slug',$produk->slug ?? '') }}"></label>
<small id="slug-help">Bagian alamat setelah /katalog/. Mengganti slug produk yang sudah terbit mengubah tautannya.</small>
@if(!isset($produk))<button hidden type="button" data-suggest-slug class="bk-secondary">Gunakan slug dari nama</button>@endif
<label>Kategori <select required name="kategori_id">@foreach($kategoris as $k)<option value="{{ $k->id }}" @selected(old('kategori_id',$produk->kategori_id ?? null)==$k->id)>{{ $k->nama }}{{ $k->aktif ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
@if($jenisTerkunci ?? false)
<input type="hidden" name="jenis" value="{{ $produk->jenis }}"><p><strong>Jenis: {{ ucfirst($produk->jenis) }}</strong><br>Jenis tidak dapat diubah karena produk ini sudah tercatat dalam pesanan.</p>
@else<label>Jenis <select name="jenis">@foreach(['jual','sewa','custom'] as $j)<option value="{{ $j }}" @selected(old('jenis',$produk->jenis ?? '')===$j)>{{ ['jual'=>'Siap jual','sewa'=>'Sewa','custom'=>'Custom'][$j] }}</option>@endforeach</select></label>@endif
<label>Harga dasar <input type="number" required min="0" name="harga_dasar" value="{{ old('harga_dasar',$produk->harga_dasar ?? 0) }}"></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi',$produk->deskripsi ?? '') }}</textarea></label>
<label data-kind-setting="custom">Waktu produksi custom (hari) <select name="lead_custom_hari">@foreach([1,2] as $v)<option @selected(old('lead_custom_hari',$produk->lead_custom_hari ?? 1)==$v)>{{ $v }}</option>@endforeach</select></label>
<div data-kind-setting="jual sewa"><input type="hidden" name="izin_hari_sama" value="0"><label><input type="checkbox" name="izin_hari_sama" value="1" @checked(old('izin_hari_sama',$produk->izin_hari_sama ?? false))> Izinkan hari yang sama</label></div>
<button class="btn-primary" @disabled($kategoris->isEmpty())>{{ isset($produk) ? 'Simpan perubahan' : 'Simpan sebagai draf' }}</button>@if($kategoris->isEmpty())<p role="alert">Belum ada kategori aktif. Sediakan kategori aktif sebelum membuat draf.</p>@endif</form>

@extends('layouts.admin')
@section('title','Opsi checkout · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $product->id }}">Kembali ke kelola katalog</x-back-link>
@include('owner.katalog.sections',['productId'=>$product->id])
<header><p class="kc-kicker">Owner · Personalisasi</p><h1>Opsi checkout {{ $product->nama }}</h1><p>Opsi melengkapi varian. Harga tambahan dihitung per produk; tipe satuan juga dikalikan jumlah tambahan yang dipilih.</p></header>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(session('option_warning'))<p role="alert">{{ session('option_warning') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('admin.katalog.manage-options',['id'=>$product->id]+$context) }}" data-pending="Menyimpan opsi…" class="bk-product-editor grid gap-3 p-4">@csrf
<h2>{{ $selected ? 'Edit opsi' : 'Tambah opsi' }}</h2><input type="hidden" name="asal" value="opsi">
@if($selected)<input type="hidden" name="id" value="{{ $selected->id }}"><input type="hidden" name="versi_opsi" value="{{ old('versi_opsi',$stamp) }}"><input type="hidden" name="tipe" value="{{ $selected->tipe }}"><p>Tipe: {{ ucfirst($selected->tipe) }} (tetap)</p>
@else<label>Tipe opsi <select name="tipe">@foreach(['teks','pilihan','satuan'] as $type)<option @selected(old('tipe')===$type)>{{ $type }}</option>@endforeach</select></label>@endif
<label>Nama opsi <input name="nama" required maxlength="80" value="{{ old('nama',$selected->nama ?? '') }}"></label>
<label>Urutan tampil <input type="number" required min="0" max="65535" name="urutan" value="{{ old('urutan',$selected->urutan ?? 0) }}"></label>
<input type="hidden" name="wajib" value="0"><label><input type="checkbox" name="wajib" value="1" @checked(old('wajib',$selected->wajib ?? false))> Wajib diisi saat checkout</label>
@if(!$selected || $selected->tipe!=='teks')
@foreach($values as $i=>$v)<fieldset><legend>Pilihan {{ $i+1 }}</legend><input type="hidden" name="nilai[{{ $i }}][id]" value="{{ $v->id }}">
<label>Label pilihan <input required maxlength="80" name="nilai[{{ $i }}][label]" value="{{ old('nilai.'.$i.'.label',$v->label) }}"></label>
<label>Tambahan (Rp) <input type="number" min="0" max="2147482648" required name="nilai[{{ $i }}][harga_tambahan]" value="{{ old('nilai.'.$i.'.harga_tambahan',$v->harga_tambahan) }}"></label>
<input type="hidden" name="nilai[{{ $i }}][aktif]" value="0"><label><input type="checkbox" name="nilai[{{ $i }}][aktif]" value="1" @checked(old('nilai.'.$i.'.aktif',$v->aktif))> Tawarkan pilihan ini</label></fieldset>@endforeach
@if($values->count()<50)<fieldset><legend>Pilihan baru (untuk tipe pilihan atau satuan)</legend><p>Kosongkan label jika tidak menambah pilihan.</p>
<label>Label baru <input maxlength="80" name="baru[label]" value="{{ old('baru.label') }}"></label><input type="hidden" name="baru[aktif]" value="1">
<label>Tambahan pilihan baru (Rp) <input type="number" min="0" max="2147482648" name="baru[harga_tambahan]" value="{{ old('baru.harga_tambahan',0) }}"></label></fieldset>@endif
@endif
<button class="btn-primary">Simpan opsi checkout</button>
@if($selected)<a href="{{ route('admin.katalog.options',['id'=>$product->id]+$context) }}">Tutup editor dan tambah opsi lain</a>@endif
</form><h2>Opsi produk</h2>
@forelse($options as $o)<article class="bk-task"><h3>{{ $o->nama }}</h3><p>{{ ucfirst($o->tipe) }} · {{ $o->wajib ? 'Wajib' : 'Opsional' }}</p><a class="bk-secondary" href="{{ route('admin.katalog.options',['id'=>$product->id,'edit'=>$o->id]+$context) }}">Edit opsi {{ $o->nama }}</a></article>@empty<p>Belum ada opsi checkout.</p>@endforelse</section>
@endsection

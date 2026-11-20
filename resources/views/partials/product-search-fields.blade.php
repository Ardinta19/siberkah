@php($selected = request()->routeIs('catalog.index') ? request()->only(['q','kategori','jenis','tanggal','urut']) : [])
<label>Nama produk<input id="{{ $prefix }}-query" name="q" type="search" maxlength="150" placeholder="Contoh: buket mawar" value="{{ $selected['q'] ?? '' }}" autocomplete="off"></label>
<div class="bk-search-facets">
    <label>Kategori<select name="kategori"><option value="">Semua kategori</option>@foreach($searchCategories as $category)<option value="{{ $category->id }}" @selected((string)($selected['kategori'] ?? '') === (string)$category->id)>{{ $category->nama }}</option>@endforeach</select></label>
    <label>Jenis produk<select name="jenis"><option value="">Semua jenis</option>@foreach(['sewa'=>'Sewa','jual'=>'Siap jual','custom'=>'Custom'] as $type=>$label)<option value="{{ $type }}" @selected(($selected['jenis'] ?? '') === $type)>{{ $label }}</option>@endforeach</select></label>
    @include('partials.rental-date',['day'=>$selected['tanggal'] ?? null,'required'=>false,'dateId'=>$prefix.'-date'])
    <label>Urutan<select name="urut"><option value="">Urutan katalog</option><option value="harga_naik" @selected(($selected['urut'] ?? '')==='harga_naik')>Harga terendah</option><option value="harga_turun" @selected(($selected['urut'] ?? '')==='harga_turun')>Harga tertinggi</option></select></label>
</div>

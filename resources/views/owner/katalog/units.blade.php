@extends('layouts.admin')
@section('title','Unit sewa · SIBERKAH')
@section('content')
<section class="bk-owner-list">
    @php($operational = request()->routeIs('admin.inventaris.units'))
    @php($unitRoute = $operational ? 'admin.inventaris.units' : 'admin.katalog.units')
    @if($operational)<x-back-link href="{{ route('admin.inventaris',$context) }}#produk-{{ $product->id }}">Kembali ke inventaris sewa</x-back-link>@else
    <x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $product->id }}">Kembali ke kelola katalog</x-back-link>
    @include('owner.katalog.sections',['productId'=>$product->id])
    @endif
    <header><p class="kc-kicker">{{ $operational ? 'Operasional' : 'Owner' }} · Inventaris sewa</p><h1>Unit fisik {{ $product->nama }}</h1><p>Satu kode untuk setiap benda yang disewakan. Status aktif tidak berarti unit kosong pada semua tanggal.</p></header>
    @if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
    @if(session('unit_warning'))<p role="alert" class="bk-notice">{{ session('unit_warning') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($variants->isEmpty())<p>Varian diperlukan sebelum unit fisik dapat didaftarkan. Hubungi Owner untuk pengaturan produk.</p>@unless($operational)<a class="bk-secondary" href="{{ route('admin.katalog.variants',['id'=>$product->id]+$context) }}">Tambah varian produk</a>@endunless
    @else
    <form id="editor-unit" class="bk-product-editor grid gap-3 p-4" data-pending="Menyimpan unit…" method="post" action="{{ route($operational ? 'admin.inventaris.save' : 'admin.katalog.manage-units',['id'=>$product->id]+$unitContext) }}">@csrf
        <h2>{{ $selected ? 'Edit unit '.$selected->kode : 'Daftarkan unit' }}</h2><input type="hidden" name="asal" value="unit">
        @if($selected)
            <input type="hidden" name="id" value="{{ $selected->id }}"><input type="hidden" name="versi_unit" value="{{ old('versi_unit',$stamp) }}">
            <input type="hidden" name="kode" value="{{ $selected->kode }}"><input type="hidden" name="varian_produk_id" value="{{ $selected->varian_produk_id }}">
            <p>Varian: <strong>{{ $selected->varian }}</strong>. Kode fisik dan varian tidak dapat diganti.</p>
        @else
            <label>Kode fisik unik <input name="kode" required maxlength="20" value="{{ old('kode') }}"></label>
            <label>Varian <select name="varian_produk_id" required>@foreach($variants as $v)<option value="{{ $v->id }}" @selected(old('varian_produk_id')==$v->id)>{{ $v->nama }}{{ $v->aktif ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
        @endif
        <label>Status unit <select name="status">@foreach($states as $state)<option value="{{ $state }}" @selected(old('status',$selected->status ?? 'aktif')===$state)>{{ ucfirst($state) }}</option>@endforeach</select></label>
        <label>Catatan kondisi (opsional) <textarea name="catatan" maxlength="200">{{ old('catatan',$selected->catatan ?? '') }}</textarea></label>
        <button class="btn-primary">Simpan unit</button>
        @if($selected)<a href="{{ route($unitRoute,['id'=>$product->id]+$unitContext) }}#daftar-unit">Tutup editor unit</a>@endif
    </form>@endif
    <div id="daftar-unit"><h2>Daftar unit ({{ $units->total() }})</h2>
    <form method="get" role="search" aria-label="Pencarian unit fisik" class="bk-product-editor grid gap-3 p-4" action="{{ route($unitRoute,$product->id) }}#daftar-unit">
        @foreach($context as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <label>Cari kode fisik <input type="search" name="cari_kode" maxlength="20" value="{{ $filters['cari_kode'] ?? '' }}"></label>
        @isset($filters['cari_kode'])<a href="{{ route($unitRoute,['id'=>$product->id]+$context+array_intersect_key($filters,['status_unit'=>true])) }}#daftar-unit">Hapus pencarian kode</a>@endisset
        <label>Filter kondisi <select name="status_unit"><option value="">Semua kondisi</option>@foreach($states as $state)<option value="{{ $state }}" @selected(($filters['status_unit'] ?? '')===$state)>{{ ucfirst($state) }}</option>@endforeach</select></label>
        <button class="bk-secondary">Tampilkan unit</button>
        @isset($filters['status_unit'])<a href="{{ route($unitRoute,['id'=>$product->id]+$context+array_intersect_key($filters,['cari_kode'=>true])) }}#daftar-unit">Reset filter kondisi</a>@endisset
    </form>
    @forelse($units as $unit)<article class="bk-task"><header><h3>{{ $unit->kode }}</h3><span class="bk-order-status">{{ ucfirst($unit->status) }}</span></header><p>{{ $unit->varian }}</p>
        @if($unit->catatan)<p>{{ $unit->catatan }}</p>@endif
        <a class="bk-secondary" href="{{ route($unitRoute,['id'=>$product->id,'edit'=>$unit->id]+$unitContext) }}#editor-unit">Edit unit {{ $unit->kode }}</a>
    </article>@empty<p>{{ $filters ? 'Tidak ada unit yang cocok dengan pencarian atau filter.' : 'Belum ada unit terdaftar.' }}</p>@endforelse
    {{ $units->links('partials.pagination') }}</div>
</section>
@endsection

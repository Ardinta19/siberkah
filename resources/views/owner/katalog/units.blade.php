@extends('layouts.admin')
@section('title','Unit sewa · SIBERKAH')
@section('content')
<section class="bk-owner-list">
    <x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $product->id }}">Kembali ke kelola katalog</x-back-link>
    @include('owner.katalog.sections',['productId'=>$product->id])
    <header><p class="kc-kicker">Owner · Inventaris sewa</p><h1>Unit fisik {{ $product->nama }}</h1><p>Satu kode untuk setiap benda yang disewakan. Status aktif tidak berarti unit kosong pada semua tanggal.</p></header>
    @if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
    @if(session('unit_warning'))<p role="alert" class="bk-notice">{{ session('unit_warning') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($variants->isEmpty())<p>Tambahkan varian sebelum mendaftarkan unit fisik.</p><a class="bk-secondary" href="{{ route('admin.katalog.variants',['id'=>$product->id]+$context) }}">Tambah varian produk</a>
    @else
    <form id="editor-unit" class="bk-product-editor grid gap-3 p-4" data-pending="Menyimpan unit…" method="post" action="{{ route('admin.katalog.manage-units',['id'=>$product->id]+$unitContext) }}">@csrf
        <h2>{{ $selected ? 'Edit unit '.$selected->kode : 'Daftarkan unit' }}</h2><input type="hidden" name="asal" value="unit">
        @if($selected)
            <input type="hidden" name="id" value="{{ $selected->id }}"><input type="hidden" name="versi_unit" value="{{ old('versi_unit',$stamp) }}">
            <input type="hidden" name="kode" value="{{ $selected->kode }}"><input type="hidden" name="varian_produk_id" value="{{ $selected->varian_produk_id }}">
            <p>Varian: <strong>{{ $selected->varian }}</strong>. Kode fisik dan varian tidak dapat diganti.</p>
        @else
            <label>Kode fisik unik <input name="kode" required maxlength="20" value="{{ old('kode') }}"></label>
            <label>Varian <select name="varian_produk_id" required>@foreach($variants as $v)<option value="{{ $v->id }}" @selected(old('varian_produk_id')==$v->id)>{{ $v->nama }}{{ $v->aktif ? '' : ' (nonaktif)' }}</option>@endforeach</select></label>
        @endif
        <label>Status unit <select name="status">@foreach(['aktif','perawatan','rusak','pensiun'] as $state)<option value="{{ $state }}" @selected(old('status',$selected->status ?? 'aktif')===$state)>{{ ucfirst($state) }}</option>@endforeach</select></label>
        <label>Catatan kondisi (opsional) <textarea name="catatan" maxlength="200">{{ old('catatan',$selected->catatan ?? '') }}</textarea></label>
        <button class="btn-primary">Simpan unit</button>
        @if($selected)<a href="{{ route('admin.katalog.units',['id'=>$product->id]+$unitContext) }}#daftar-unit">Tutup editor unit</a>@endif
    </form>@endif
    <div id="daftar-unit"><h2>Daftar unit ({{ $units->total() }})</h2>
    @forelse($units as $unit)<article class="bk-task"><header><h3>{{ $unit->kode }}</h3><span class="bk-order-status">{{ ucfirst($unit->status) }}</span></header><p>{{ $unit->varian }}</p>
        @if($unit->catatan)<p>{{ $unit->catatan }}</p>@endif
        <a class="bk-secondary" href="{{ route('admin.katalog.units',['id'=>$product->id,'edit'=>$unit->id]+$unitContext) }}#editor-unit">Edit unit {{ $unit->kode }}</a>
    </article>@empty<p>Belum ada unit pada halaman ini.</p>@endforelse
    {{ $units->links('partials.pagination') }}</div>
</section>
@endsection

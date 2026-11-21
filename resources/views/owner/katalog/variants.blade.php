@extends('layouts.admin')
@section('title','Varian produk · SIBERKAH')
@section('content')
<section class="bk-owner-list">
    <x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $product->id }}">Kembali ke kelola katalog</x-back-link>
    @include('owner.katalog.sections',['productId'=>$product->id])
    <header><p class="kc-kicker">Owner · Varian</p><h1>Varian {{ $product->nama }}</h1><p>Harga dan status varian dikelola terpisah dari status terbit produk.</p></header>
    @if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
    @if(session('variant_warning'))<p role="alert" class="bk-notice">{{ session('variant_warning') }}</p>@endif
    @if($errors->any())<ul role="alert" class="bk-notice">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <form id="editor-varian" class="bk-product-editor grid gap-3 p-4" data-pending="Menyimpan varian…" method="post" action="{{ route('admin.katalog.manage-variants',['id'=>$product->id]+$context) }}">@csrf
        <h2>{{ $selected ? 'Edit varian' : 'Tambah varian' }}</h2><input type="hidden" name="asal" value="varian">
        @if($selected)<input type="hidden" name="id" value="{{ $selected->id }}"><input type="hidden" name="versi_varian" value="{{ old('versi_varian',$stamp) }}">@endif
        <label>Nama varian <input required maxlength="80" name="nama" value="{{ old('nama',$selected->nama ?? '') }}"></label>
        <label>Harga varian (Rp) <input required type="number" min="0" max="2147482648" name="harga" value="{{ old('harga',$selected->harga ?? '') }}"></label>
        @if($product->jenis==='jual')<label>Stok tersedia <input required type="number" min="0" max="65535" name="stok" value="{{ old('stok',$selected->stok ?? 0) }}"></label>
            <small>Isi jumlah yang tersedia setelah penyesuaian, bukan jumlah tambahan.</small>
        @elseif($product->jenis==='sewa')<p>Ketersediaan sewa mengikuti unit fisik dan tanggal, bukan angka stok varian.</p>@endif
        <label>Status varian <select name="aktif"><option value="1" @selected(old('aktif',$selected->aktif ?? 1)==1)>Aktif</option><option value="0" @selected(old('aktif',$selected->aktif ?? 1)==0)>Nonaktif</option></select></label>
        <button class="btn-primary">Simpan varian</button>
        @if($selected)<a href="{{ route('admin.katalog.variants',['id'=>$product->id]+$context) }}#varian-{{ $selected->id }}">Batalkan penyuntingan</a>@endif
    </form>
    @forelse($variants as $v)<article class="bk-task" id="varian-{{ $v->id }}"><header><h2>{{ $v->nama }}</h2><span class="bk-order-status">{{ $v->aktif ? 'Aktif' : 'Nonaktif' }}</span></header>
        <p>Rp{{ number_format($v->harga,0,',','.') }}@if($product->jenis==='jual') · Stok tersedia: {{ $v->stok }}@endif</p>
        <a class="bk-secondary" href="{{ route('admin.katalog.variants',['id'=>$product->id,'edit'=>$v->id]+$context) }}#editor-varian">Edit {{ $v->nama }}</a>
    </article>@empty<p class="kc-empty">Belum ada varian untuk produk ini.</p>@endforelse
</section>
@endsection

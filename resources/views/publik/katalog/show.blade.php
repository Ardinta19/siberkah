@extends('layouts.guest')
@section('title', $produk->nama.' · SIBERKAH')
@section('content')
@php($koleksi = collect((array)request('koleksi', []))->only(['q','jenis','kategori','tanggal','urut','page'])->filter(fn ($value) => is_scalar($value))->all())
<article data-product-detail class="container-siberkah grid gap-6 py-8 md:grid-cols-2">
    @include('partials.product-gallery')
    <section class="bk-product-panel">
        <a class="text-primary underline" href="{{ route('catalog.index',$koleksi) }}{{ request()->has('koleksi') ? '#produk-'.$produk->id : '#produk-katalog' }}">Kembali ke katalog</a>
        <p class="kc-kicker">{{ $produk->kategori?->nama }} · {{ ucfirst($produk->jenis) }}</p>
        <h1 class="font-display text-3xl font-bold">{{ $produk->nama }}</h1>
        <p>{{ $produk->deskripsi }}</p>
        <strong>{{ $produk->harga_tampil !== null ? 'Mulai Rp'.number_format($produk->harga_tampil,0,',','.') : 'Harga belum tersedia' }}</strong>
        @if($varian->isNotEmpty())
            <h2 class="font-bold">Pilihan varian</h2>
            <ul class="bk-options">@foreach($varian as $v)<li>{{ $v->nama }} · Rp{{ number_format($v->harga, 0, ',', '.') }}</li>@endforeach</ul>
        @endif
        @if($produk->jenis === 'sewa')
            <form class="bk-form-row" data-pending="Memeriksa…">@foreach($koleksi as $k=>$v)<input type="hidden" name="koleksi[{{ $k }}]" value="{{ $v }}">@endforeach
                @include('partials.rental-date',['day'=>$tanggalRequest,'required'=>true])
                <button class="bk-secondary">Cek unit</button></form>
            @if($tersedia !== null)<p role="status">{{ $tersedia ? 'Ada unit pada tanggal ini. Unit belum dipesan.' : 'Tidak tersedia pada tanggal ini.' }}</p>@endif
            @if($saran)
                <p>Tanggal kosong terdekat:</p><ul class="bk-options">@foreach($saran as $hari)<li><a href="{{ route('catalog.show', ['slug' => $produk->slug, 'tanggal' => $hari,'koleksi'=>$koleksi]) }}">{{ $hari }}</a></li>@endforeach</ul>
            @endif
        @endif
        @if($errors->any())<p role="alert" class="bk-notice">{{ $errors->first() }}</p>@endif
        <form class="bk-form-row" data-pending="Menyimpan…" method="post" action="{{ route('cart.save',$produk->id) }}">@csrf @method('put')
            <p class="w-full kc-kicker">Simpan untuk nanti</p>
            @include('partials.quantity',['quantity'=>session('cart.'.$produk->id,1),'itemId'=>$produk->id])
            <button class="kc-action">Simpan ke keranjang</button>
        </form>
        @if(config('checkout.staging') && app()->environment('local','testing','staging'))
            @auth('customer')
                <a class="bk-checkout" href="{{ route('checkout.product', ['slug' => $produk->slug, 'tanggal' => $tanggalRequest]) }}">Pesan produk ini</a>
            @else
                <a class="bk-checkout" href="{{ route('otp.form') }}">Masuk untuk memesan</a>
            @endauth
        @else
            <p class="text-sm">Pemesanan online belum dibuka.</p>
        @endif
    </section>
</article>
@endsection

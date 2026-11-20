@extends('layouts.guest')
@section('title', $produk->nama.' · SIBERKAH')
@section('content')
@php($koleksi = collect((array)request('koleksi', []))->only(['q','jenis','kategori','tanggal','urut','page'])->filter(fn ($value) => is_scalar($value))->all())
<article class="container-siberkah grid gap-6 py-8 md:grid-cols-2">
    <div class="overflow-hidden rounded-lg bg-primary-50">
        @if($produk->foto_utama && !$gambarProduk->contains('jalur_berkas', $produk->foto_utama))
            <img src="{{ asset('storage/'.$produk->foto_utama) }}" class="w-full object-cover" alt="{{ $produk->nama }}">
        @endif
        @if(!$produk->foto_utama && $gambarProduk->isEmpty())<div class="kc-media"><img class="kc-monogram" src="{{ asset('images/berkah-kreatif.webp') }}" alt="Foto produk belum tersedia"></div>@endif
        @foreach($gambarProduk as $gambar)
            <img loading="lazy" alt="{{ $gambar->teks_alternatif ?? $produk->nama }}" src="{{ asset('storage/'.$gambar->jalur_berkas) }}">
        @endforeach
    </div>
    <section class="space-y-4">
        <a class="text-primary underline" href="{{ route('catalog.index',$koleksi) }}">Kembali ke katalog</a>
        <h1 class="font-display text-3xl font-bold">{{ $produk->nama }}</h1>
        <p>{{ $produk->deskripsi }}</p>
        <strong>{{ $produk->harga_tampil !== null ? 'Mulai Rp'.number_format($produk->harga_tampil,0,',','.') : 'Harga belum tersedia' }}</strong>
        @if($varian->isNotEmpty())
            <h2 class="font-bold">Varian</h2>
            <ul>@foreach($varian as $v)<li>{{ $v->nama }} · Rp{{ number_format($v->harga, 0, ',', '.') }}</li>@endforeach</ul>
        @endif
        @if($produk->jenis === 'sewa')
            <form class="space-y-2">@foreach($koleksi as $k=>$v)<input type="hidden" name="koleksi[{{ $k }}]" value="{{ $v }}">@endforeach<label>Tanggal sewa <input type="date" required name="tanggal" value="{{ $tanggalRequest }}"></label><button class="btn-outline">Cek unit</button></form>
            @if($tersedia !== null)<p role="status">{{ $tersedia ? 'Ada unit pada tanggal ini. Unit belum dipesan.' : 'Tidak tersedia pada tanggal ini.' }}</p>@endif
            @if($saran)
                <p>Tanggal kosong terdekat:</p><ul>@foreach($saran as $hari)<li><a href="{{ route('catalog.show', ['slug' => $produk->slug, 'tanggal' => $hari,'koleksi'=>$koleksi]) }}">{{ $hari }}</a></li>@endforeach</ul>
            @endif
        @endif
        <form method="post" action="{{ route('cart.save',$produk->id) }}">@csrf @method('put')
            <label>Jumlah <input type="number" required min="1" max="100" name="jumlah" value="{{ session('cart.'.$produk->id,1) }}" class="w-16 border rounded p-2"></label>
            <button class="kc-action">Simpan ke keranjang</button>
        </form>
        @if(config('checkout.staging') && app()->environment('local','testing','staging'))
            @auth('customer')
                <a class="btn-primary" href="{{ route('checkout.product', ['slug' => $produk->slug, 'tanggal' => $tanggalRequest]) }}">Pesan produk ini</a>
            @else
                <a class="btn-primary" href="{{ route('otp.form') }}">Masuk untuk memesan</a>
            @endauth
        @else
            <p class="text-sm">Pemesanan online belum dibuka.</p>
        @endif
    </section>
</article>
@endsection

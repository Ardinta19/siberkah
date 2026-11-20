@extends('layouts.guest')
@section('title', $produk->nama.' · SIBERKAH')
@section('content')
<article class="container-siberkah grid gap-6 py-8 md:grid-cols-2">
    <div class="overflow-hidden rounded-lg bg-primary-50">
        @if($produk->foto_utama && !$gambarProduk->contains('jalur_berkas', $produk->foto_utama))
            <img src="{{ asset('storage/'.$produk->foto_utama) }}" class="w-full object-cover" alt="{{ $produk->nama }}">
        @endif
        @foreach($gambarProduk as $gambar)
            <img loading="lazy" alt="{{ $gambar->teks_alternatif ?? $produk->nama }}" src="{{ asset('storage/'.$gambar->jalur_berkas) }}">
        @endforeach
    </div>
    <section class="space-y-4">
        <a class="text-primary underline" href="{{ route('catalog.index') }}">Kembali ke katalog</a>
        <h1 class="font-display text-3xl font-bold">{{ $produk->nama }}</h1>
        <p>{{ $produk->deskripsi }}</p>
        <strong>Rp{{ number_format($produk->harga_dasar, 0, ',', '.') }}</strong>
        @if($varian->isNotEmpty())
            <h2 class="font-bold">Varian</h2>
            <ul>@foreach($varian as $v)<li>{{ $v->nama }} · Rp{{ number_format($v->harga, 0, ',', '.') }}</li>@endforeach</ul>
        @endif
        @if($produk->jenis === 'sewa')
            <form class="space-y-2"><label>Tanggal sewa <input type="date" required name="tanggal" value="{{ $tanggalRequest }}"></label><button class="btn-outline">Cek unit</button></form>
            @if($tersedia !== null)<p role="status">{{ $tersedia ? 'Ada unit pada tanggal ini. Unit belum dipesan.' : 'Tidak tersedia pada tanggal ini.' }}</p>@endif
            @if($saran)
                <p>Tanggal kosong terdekat:</p><ul>@foreach($saran as $hari)<li><a href="{{ route('catalog.show', ['slug' => $produk->slug, 'tanggal' => $hari]) }}">{{ $hari }}</a></li>@endforeach</ul>
            @endif
        @endif
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

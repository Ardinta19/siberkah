@extends('layouts.guest')
@section('title','Beranda · Berkah Kreatif')
@section('content')
<section class="koleksi"><div class="kc-wrap"><div class="kc-hero kc-hero-reveal"><div class="kc-intro"><p class="kc-kicker">Berkah Kreatif Atelier</p><h1>Bunga untuk <em>ceritamu.</em></h1><p>Temukan buket untuk dibawa pulang dan papan ucapan untuk disewa.</p><a class="kc-discover" href="{{ route('catalog.index') }}">Jelajahi katalog →</a></div><div class="kc-still"><img src="{{ asset('images/katalog-editorial.webp') }}" alt=""></div><button type="button" class="kc-replay" data-replay hidden><span aria-hidden="true">✿</span> Putar animasi</button><small class="kc-motion-note">Gerakan dikurangi sesuai pengaturan perangkat.</small></div>
@if($categories->isNotEmpty())
<section aria-labelledby="home-collections">
    <div class="kc-bar"><p class="kc-kicker">Pilihan Berkah Kreatif</p><h2 id="home-collections">Dari setiap kategori</h2></div>
    <div class="kc-grid">
        @foreach($categories as $category)
            @if($product = $category->produkAktif->first())
                <article class="kc-card">
                    <div class="kc-media">@if($product->foto_utama)<img class="kc-product-photo" src="{{ asset('storage/'.$product->foto_utama) }}" loading="lazy" alt="{{ $product->nama }}">@else<img class="kc-monogram" src="{{ asset('images/berkah-kreatif.webp') }}" alt="">@endif</div>
                    <div class="kc-info"><p class="kc-kicker">{{ $category->nama }}</p><h3><a href="{{ route('catalog.show',$product->slug) }}">{{ $product->nama }}</a></h3></div>
                    <div class="kc-foot"><a class="kc-action" href="{{ route('catalog.index',['kategori'=>$category->id]) }}">Jelajahi {{ $category->nama }} →</a></div>
                </article>
            @endif
        @endforeach
    </div>
</section>
@endif
</div></section>
@endsection

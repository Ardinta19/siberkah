@extends('layouts.guest')
@section('title','Beranda · Berkah Kreatif')
@section('content')
<section class="koleksi"><div class="kc-wrap"><div class="kc-hero"><div class="kc-intro"><p class="kc-kicker">Berkah Kreatif Atelier</p><h1>Bunga untuk <em>ceritamu.</em></h1><p>Temukan buket untuk dibawa pulang dan papan ucapan untuk disewa.</p><a class="kc-discover" href="{{ route('catalog.index') }}">Jelajahi katalog →</a></div><div class="kc-still"><img src="{{ asset('images/katalog-editorial.webp') }}" alt=""></div></div></div></section>
@endsection

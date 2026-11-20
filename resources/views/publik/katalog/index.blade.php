@extends('layouts.guest')
@section('title', 'Katalog Produk · SIBERKAH')
@section('content')
<section class="koleksi" aria-labelledby="koleksi-title">
  <div class="kc-wrap">
    <p class="kc-crumb">Berkah Kreatif <span aria-hidden="true"> / </span> Katalog produk</p>
    <header class="kc-hero">
      <div class="kc-intro">
        <p class="kc-kicker">✧ &nbsp; Berkah Kreatif Atelier</p>
        <h1 id="koleksi-title">Koleksi <em>Estetik.</em></h1>
        <p>Papan ucapan dan rangkaian bunga untuk momen yang ingin Anda kenang.</p>
        <a class="kc-discover" href="#produk-katalog">Jelajahi produk <span aria-hidden="true">↓</span></a>
      </div>
      <div class="kc-still" aria-hidden="true"><img src="{{ asset('images/katalog-editorial.webp') }}" alt="" fetchpriority="high"><span>STUDI FLORAL &nbsp; / &nbsp; 01</span></div>
    </header>
    <div id="produk-katalog" class="kc-bar"><p class="kc-kicker">01 &nbsp;/&nbsp; Direktori produk</p><h2>Temukan pilihan Anda</h2></div>
    <div class="kc-directory">
    <aside class="kc-tools" aria-label="Penyaring katalog"><p class="kc-rail-title">Saring koleksi</p>
    <form class="kc-search" action="{{ route('catalog.index') }}" role="search">
      @if($filterKategori)<input type="hidden" name="kategori" value="{{ $filterKategori }}">@endif
      <label>Pencarian<input type="search" name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nama produk atau kategori"></label>
      <label>Jenis produk<select name="jenis"><option value="">Semua jenis</option>@foreach(['sewa'=>'Sewa','jual'=>'Siap jual','custom'=>'Custom'] as $jenis=>$nama)<option value="{{ $jenis }}" @selected($filterJenis === $jenis)>{{ $nama }}</option>@endforeach</select></label>
      <label>Tanggal sewa<input type="date" name="tanggal" value="{{ $filterTanggal }}"></label>
      <label>Urutan<select name="urut"><option value="">Urutan katalog</option><option value="harga_naik" @selected(request('urut') === 'harga_naik')>Harga terendah</option><option value="harga_turun" @selected(request('urut') === 'harga_turun')>Harga tertinggi</option></select></label>
      <button class="kc-action">Cari produk</button>
    </form>
    <nav class="kc-tags" aria-label="Kategori produk">
      @foreach(collect([null => 'Semua'])->union($kategoris->pluck('nama','id')) as $id => $nama)
        <a href="{{ route('catalog.index',array_filter(array_merge(request()->only(['q','jenis','tanggal','urut']),['kategori'=>$id]),fn ($v)=>$v !== '')) }}" @if((string)$filterKategori === (string)$id) aria-current="page" @endif>{{ $nama }}</a>
      @endforeach
    </nav>
    </aside>
    <div class="kc-results">
    <div class="kc-meta"><p role="status"><strong>{{ $produk->total() }}</strong> produk ditemukan @if($filterTanggal) · Tersedia untuk sewa pada {{ $filterTanggal }} @endif</p>@if(request()->anyFilled(['kategori','jenis','q','tanggal','urut']))<a href="{{ route('catalog.index') }}">Reset filter ↺</a>@endif</div>
    <div class="kc-grid">
      @forelse($produk as $p)
        <article class="kc-card">
          <div class="kc-media">
            @if($p->foto_utama)<img class="kc-product-photo" src="{{ asset('storage/'.$p->foto_utama) }}" loading="lazy" alt="{{ $p->nama }}">
            @else<img class="kc-monogram" src="{{ asset('images/berkah-kreatif.webp') }}" alt="">@endif
            <span class="kc-type">{{ ['sewa'=>'Sewa','jual'=>'Siap jual','custom'=>'Custom'][$p->jenis] ?? ucfirst($p->jenis) }}</span>
            <button type="button" data-favorite="{{ $p->id }}" aria-label="Favorit: {{ $p->nama }}" aria-pressed="false">♡</button>
          </div>
          <div class="kc-info"><p class="kc-kicker">{{ $p->kategori?->nama }}</p><h3>{{ $p->nama }}</h3>
            <details><summary>Ringkasan · {{ $p->varianProduk->count() }} varian</summary><p>{{ $p->deskripsi ?: 'Belum ada deskripsi.' }}</p></details>
          </div>
          <div class="kc-foot"><div><small>{{ $p->jenis === 'sewa' ? 'Tarif dasar sewa' : 'Harga dasar' }}</small><strong>Rp{{ number_format($p->harga_dasar,0,',','.') }}</strong></div><a class="kc-action" href="{{ route('catalog.show',['slug'=>$p->slug,'tanggal'=>$filterTanggal]) }}">Lihat detail <span aria-hidden="true">↗</span></a></div>
        </article>
      @empty
        <div class="kc-empty"><span aria-hidden="true">✧</span><h3>Tidak ada produk yang ditemukan.</h3><p>Coba kata lain atau periksa kembali filter pencarian Anda.</p><a class="kc-action" href="{{ route('catalog.index') }}">Lihat semua produk</a></div>
      @endforelse
    </div>
    {{ $produk->links() }}
    </div></div>
    <nav class="kc-dock" aria-label="Navigasi cepat katalog">
      <a href="#produk-katalog" aria-current="page"><span aria-hidden="true">▦</span>Katalog</a>
      <a href="{{ route('pesanan.index') }}"><span aria-hidden="true">☷</span>Pesanan</a>
      <a href="{{ auth('customer')->check() ? route('profile.edit') : route('otp.form') }}"><span aria-hidden="true">◎</span>Akun</a>
    </nav>
  </div>
</section>
@endsection

@extends('layouts.guest')
@section('title', 'Katalog Produk · SIBERKAH')
@section('content')
<section class="koleksi" aria-labelledby="koleksi-title">
  <div class="kc-wrap">
    <p class="kc-crumb">Berkah Kreatif <span aria-hidden="true"> / </span> Katalog produk</p>
    <header class="kc-hero kc-hero-reveal">
      <div class="kc-intro">
        <p class="kc-kicker">✧ &nbsp; Berkah Kreatif Atelier</p>
        <h1 id="koleksi-title">Koleksi <em>Estetik.</em></h1>
        <p>Papan ucapan dan rangkaian bunga untuk momen yang ingin Anda kenang.</p>
        <a class="kc-discover" href="#produk-katalog">Jelajahi produk <span aria-hidden="true">↓</span></a>
      </div>
      <div class="kc-still" aria-hidden="true"><img src="{{ asset('images/katalog-editorial.webp') }}" alt="" fetchpriority="high"></div>
    </header>
    <div id="produk-katalog" class="kc-bar"><h2>Temukan pilihan Anda</h2></div>
    <noscript><form action="{{ route('catalog.index') }}" role="search">@include('partials.product-search-fields',['prefix'=>'fallback'])<button>Cari produk</button></form></noscript>
    @if(request()->filled('q'))<p class="kc-search-note">Hasil pencarian untuk <strong>“{{ request('q') }}”</strong> · <a href="{{ route('catalog.index',request()->only(['kategori','jenis','tanggal','urut'])) }}">Hapus pencarian</a></p>@endif
    <div class="kc-directory">
    <div class="kc-results">
    <div class="kc-meta"><p role="status">@if($filterKategori){{ $kategoris->firstWhere('id',$filterKategori)?->nama }} · @endif<strong>{{ $produk->total() }}</strong> produk ditemukan @if($filterTanggal) · Tersedia untuk sewa pada {{ $filterTanggal }} @endif</p>@if(request()->anyFilled(['kategori','jenis','q','tanggal','urut']))<a href="{{ route('catalog.index') }}">Reset filter ↺</a>@endif</div>
    @if($filterJenis || request('urut'))<p class="kc-applied">Filter aktif: {{ ['jual'=>'Siap jual','sewa'=>'Sewa','custom'=>'Custom'][$filterJenis] ?? 'Semua jenis' }} @if(request('urut')) · {{ request('urut') === 'harga_naik' ? 'Harga terendah' : 'Harga tertinggi' }} @endif</p>@endif
    <div class="kc-grid">
      @forelse($produk as $p)
        <article class="kc-card">
          <div class="kc-media">
            <a class="kc-photo-link" aria-label="Lihat {{ $p->nama }}" href="{{ route('catalog.show',['slug'=>$p->slug,'tanggal'=>$filterTanggal,'koleksi'=>request()->only(['q','jenis','kategori','tanggal','urut','page'])]) }}"></a>
            @if($p->foto_utama)<img class="kc-product-photo" src="{{ asset('storage/'.$p->foto_utama) }}" loading="lazy" alt="{{ $p->nama }}">
            @else<img class="kc-monogram" src="{{ asset('images/berkah-kreatif.webp') }}" alt="">@endif
            <span class="kc-type">{{ ['sewa'=>'Sewa','jual'=>'Siap jual','custom'=>'Custom'][$p->jenis] ?? ucfirst($p->jenis) }}</span>
            <button type="button" data-favorite="{{ $p->id }}" aria-label="Favorit: {{ $p->nama }}" aria-pressed="false">♡</button>
          </div>
          <div class="kc-info"><p class="kc-kicker">{{ $p->kategori?->nama }}</p><h3>{{ $p->nama }}</h3>
            @if($p->jenis === 'sewa')<p class="kc-availability">{{ $filterTanggal ? 'Tersedia pada tanggal pilihan' : 'Pilih tanggal untuk cek unit' }}</p>@endif
            <details><summary>Ringkasan · {{ $p->varianProduk->count() }} varian</summary><p>{{ $p->deskripsi ?: 'Belum ada deskripsi.' }}</p></details>
          </div>
          <div class="kc-foot"><div><small>{{ $p->jenis === 'sewa' ? 'Tarif sewa mulai' : 'Harga mulai' }}</small><strong>{{ $p->harga_tampil !== null ? 'Rp'.number_format($p->harga_tampil,0,',','.') : 'Belum tersedia' }}</strong></div><a class="kc-action" href="{{ route('catalog.show',['slug'=>$p->slug,'tanggal'=>$filterTanggal,'koleksi'=>request()->only(['q','jenis','kategori','tanggal','urut','page'])]) }}">Lihat detail <span aria-hidden="true">↗</span></a></div>
        </article>
      @empty
        <div class="kc-empty"><span aria-hidden="true">✧</span><h3>Tidak ada produk yang ditemukan.</h3><p>Coba kata lain atau periksa kembali filter pencarian Anda.</p><a class="kc-action" href="{{ route('catalog.index') }}">Lihat semua produk</a></div>
      @endforelse
    </div>
    {{ $produk->links('partials.pagination') }}
    </div></div>

  </div>
</section>
@endsection

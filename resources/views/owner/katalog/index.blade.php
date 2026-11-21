@extends('layouts.admin')
@section('title','Kelola katalog · SIBERKAH')
@section('content')
<section class="bk-owner-list">
    <x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
    @if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
    <header><p class="kc-kicker">Owner · Produk</p><h1>Kelola katalog</h1><p>Menyimpan draf tidak langsung menampilkan produk kepada pelanggan.</p>
        <a class="bk-secondary" href="{{ route('admin.katalog.create',$context) }}">Buat draf</a></header>
    <form method="get" role="search" aria-label="Cari produk kelolaan" action="{{ route('admin.katalog.index') }}#daftar-kelola" class="bk-product-editor grid gap-3 p-4">
        @if($state)<input type="hidden" name="tampil" value="{{ $state }}">@endif
        <label>Cari nama produk <input type="search" maxlength="150" name="q" value="{{ $context['q'] ?? '' }}"></label>
        <div><button class="bk-secondary">Cari</button>
        @isset($context['q'])<a class="bk-secondary" href="{{ route('admin.katalog.index',array_filter(['tampil'=>$state])) }}#daftar-kelola">Hapus pencarian</a>@endisset</div>
    </form>
    <nav class="bk-history-filters" aria-label="Visibilitas produk">
        @foreach([''=>'Semua','publik'=>'Tampil di katalog','nonaktif'=>'Draf / arsip','kategori'=>'Kategori nonaktif'] as $key=>$label)
            <a @if(($state ?? '')===$key) aria-current="page" @endif href="{{ route('admin.katalog.index',array_filter(['tampil'=>$key])+array_intersect_key($context,['q'=>true])) }}#daftar-kelola">{{ $label }}</a>
        @endforeach
        @if($state)<a href="{{ route('admin.katalog.index',array_intersect_key($context,['q'=>true])) }}#daftar-kelola">Reset filter</a>@endif
    </nav>
    <div id="daftar-kelola"><p>{{ $produk->total() }} produk dalam daftar ini.</p>
    @forelse($produk as $p)
        <article class="bk-task" id="kelola-{{ $p->id }}"><header><h2>{{ $p->nama }}</h2>
            <span class="bk-order-status">{{ !$p->aktif ? 'Draf / arsip' : ($p->kategori?->aktif ? 'Tampil di katalog' : 'Tersembunyi · kategori nonaktif') }}</span></header>
            <p>{{ $p->kategori?->nama }} · {{ ['jual'=>'Siap jual','sewa'=>'Sewa','custom'=>'Custom'][$p->jenis] ?? $p->jenis }}</p>
            <a class="bk-secondary" href="{{ route('admin.katalog.edit',['id'=>$p->id]+$context) }}">Edit {{ $p->nama }}</a>
            <a class="bk-secondary" href="{{ route('admin.katalog.gallery',['id'=>$p->id]+$context) }}">Kelola foto {{ $p->nama }}</a>
            <a class="bk-secondary" href="{{ route('admin.katalog.variants',['id'=>$p->id]+$context) }}">Kelola varian {{ $p->nama }}</a>
            @if($p->aktif && $p->kategori?->aktif)<a class="bk-secondary" href="{{ route('catalog.show',$p->slug) }}">Lihat {{ $p->nama }} di katalog publik</a>@endif
            @if($p->aktif || $p->kategori?->aktif)
                <details class="bk-confirm"><summary>{{ $p->aktif ? 'Arsipkan' : 'Terbitkan' }}</summary>
                    <p>{{ $p->nama }} {{ $p->aktif ? 'akan diarsipkan.' : 'akan ditampilkan di katalog publik.' }}</p>
                    <form data-pending="Menyimpan status…" method="post" action="{{ route($p->aktif ? 'admin.katalog.archive' : 'admin.katalog.publish',['id'=>$p->id]+$context) }}">@csrf<button>Ya, {{ $p->aktif ? 'Arsipkan' : 'Terbitkan' }}</button></form>
                </details>
            @else<p>Kategori belum aktif. Pilih kategori aktif sebelum menerbitkan produk.</p>@endif
        </article>
    @empty<div class="kc-empty"><p>Tidak ada produk pada halaman ini.</p>
        @if($state || $produk->currentPage()>1)<a class="bk-secondary" href="{{ route('admin.katalog.index') }}">Kembali ke semua produk</a>@endif</div>@endforelse
    {{ $produk->links('partials.pagination') }}</div>
</section>
@endsection

@extends('layouts.admin')
@section('title','Inventaris unit sewa · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<header><p class="kc-kicker">Operasional · Inventaris</p><h1>Inventaris unit sewa</h1><p>Pilih produk untuk mendaftarkan benda fisik atau mengubah kondisinya. Untuk menutup ketersediaan pada satu tanggal saja, gunakan <a href="{{ route('admin.kalender') }}">kalender operasional</a>.</p></header>
<form class="bk-product-editor grid gap-3 p-4" role="search" method="get"><label>Nama produk sewa<input type="search" name="q" maxlength="150" value="{{ $context['q'] ?? '' }}"></label><button class="bk-secondary">Cari produk sewa</button>@isset($context['q'])<a href="{{ route('admin.inventaris') }}">Hapus pencarian</a>@endisset</form>
<p>{{ $products->total() }} produk sewa.</p>@forelse($products as $product)<article id="produk-{{ $product->id }}" class="bk-task"><h2>{{ $product->nama }}</h2><p>Produk {{ $product->aktif ? 'aktif' : 'nonaktif' }}</p><a class="bk-secondary" href="{{ route('admin.inventaris.units',['id'=>$product->id]+$context) }}">Kelola unit {{ $product->nama }}</a></article>@empty<p>Tidak ada produk sewa pada halaman ini.</p>@endforelse
{{ $products->links('partials.pagination') }}</section>
@endsection

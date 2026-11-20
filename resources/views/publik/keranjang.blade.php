@extends('layouts.guest')
@section('title','Keranjang · Berkah Kreatif')
@section('content')
<section class="koleksi"><div class="kc-wrap"><div class="kc-bar"><p class="kc-kicker">Berkah Kreatif / Belanja</p><h1 class="text-3xl font-bold">Keranjang Anda</h1></div>
<p class="my-4">Produk belum dipesan. Pilih varian, tanggal, dan cara bayar saat melanjutkan pesanan per produk.</p>
@if(session('cart_notice'))<p class="bk-notice" role="status">{{ session('cart_notice') }}</p>@endif
@if($errors->any())<p class="bk-notice" role="alert">{{ $errors->first() }}</p>@endif
@forelse($products as $p)
<article class="kc-card mb-3"><div class="kc-info"><a class="underline" href="{{ route('catalog.show',$p->slug) }}">{{ $p->nama }}</a><p>{{ $p->harga_tampil !== null ? 'Mulai Rp'.number_format($p->harga_tampil,0,',','.') : 'Harga belum tersedia' }}</p></div><div class="kc-foot">
<form class="bk-form-row" data-pending="Memperbarui…" method="post" action="{{ route('cart.save',$p->id) }}">@csrf @method('put') @include('partials.quantity',['quantity'=>$cart[$p->id],'itemId'=>$p->id]) <button class="bk-secondary">Perbarui</button></form>
<details class="bk-remove"><summary>Hapus</summary><p>Hapus {{ $p->nama }} dari keranjang?</p><form data-pending="Menghapus…" method="post" action="{{ route('cart.remove',$p->id) }}">@csrf @method('delete')<button class="bk-secondary">Ya, hapus</button></form></details>
@if(config('checkout.staging') && app()->environment('local','testing','staging'))<a class="kc-action" href="{{ route('checkout.product',['slug'=>$p->slug,'dari_keranjang'=>1]) }}">Lanjut pesan</a>@endif
</div></article>
@empty<p class="kc-empty">Keranjang masih kosong.</p>@endforelse
<a class="kc-discover" href="{{ route('catalog.index') }}">Kembali ke katalog</a></div></section>
@endsection

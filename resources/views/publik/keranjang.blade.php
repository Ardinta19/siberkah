@extends('layouts.guest')
@section('title','Keranjang · Berkah Kreatif')
@section('content')
<section class="koleksi"><div class="kc-wrap"><div class="kc-bar"><p class="kc-kicker">Berkah Kreatif / Belanja</p><h1 class="text-3xl font-bold">Keranjang Anda</h1></div>
<p class="my-4">Produk belum dipesan. Pilih varian, tanggal, dan cara bayar saat melanjutkan pesanan per produk.</p>
@forelse($products as $p)
<article class="kc-card mb-3"><div class="kc-info"><a class="underline" href="{{ route('catalog.show',$p->slug) }}">{{ $p->nama }}</a><p>Harga dasar Rp{{ number_format($p->harga_dasar,0,',','.') }}</p></div><div class="kc-foot">
<form method="post" action="{{ route('cart.save',$p->id) }}">@csrf @method('put') <label>Jumlah <input class="w-16 border rounded p-2" type="number" name="jumlah" required min="1" max="100" value="{{ $cart[$p->id] }}"></label> <button class="underline">Perbarui</button></form>
<form method="post" action="{{ route('cart.remove',$p->id) }}">@csrf @method('delete')<button class="underline">Hapus</button></form>
@if(config('checkout.staging') && app()->environment('local','testing','staging'))<a class="kc-action" href="{{ route('checkout.product',['slug'=>$p->slug,'dari_keranjang'=>1]) }}">Lanjut pesan</a>@endif
</div></article>
@empty<p class="kc-empty">Keranjang masih kosong.</p>@endforelse
<a class="kc-discover" href="{{ route('catalog.index') }}">Kembali ke katalog</a></div></section>
@endsection

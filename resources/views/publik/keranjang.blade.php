@extends('layouts.guest')
@section('title','Keranjang · Berkah Kreatif')
@section('content')
<section class="koleksi"><div class="kc-wrap">
    <header class="bk-bag-head"><p class="kc-kicker">Berkah Kreatif / Belanja</p><h1>Keranjang Anda</h1>
        <p>Produk belum dipesan. Pilih varian, tanggal, dan cara bayar saat melanjutkan pesanan per produk.</p>
        @if($products->isNotEmpty())<span>{{ $products->count() }} produk · {{ array_sum($cart) }} item tersimpan</span>@endif
    </header>
    @if(session('cart_notice'))<p class="bk-notice" role="status">{{ session('cart_notice') }}</p>@endif
    @if($errors->any())<p class="bk-notice" role="alert">{{ $errors->first() }}</p>@endif
    @forelse($products as $p)
        <article class="kc-card bk-bag-item" aria-labelledby="bag-title-{{ $p->id }}">
            <div class="kc-info"><h2 id="bag-title-{{ $p->id }}"><a href="{{ route('catalog.show',$p->slug) }}">{{ $p->nama }}</a></h2>
                <p>{{ $p->harga_tampil !== null ? 'Mulai Rp'.number_format($p->harga_tampil,0,',','.') : 'Harga belum tersedia' }}</p>
            </div>
            <div class="kc-foot"><form class="bk-form-row bk-bag-form" data-bag-form data-pending="Memperbarui…" method="post" action="{{ route('cart.save',$p->id) }}">
                @csrf @method('put')
                @include('partials.quantity',['quantity'=>$cart[$p->id],'itemId'=>$p->id])
                <button class="bk-secondary" data-save-quantity>Perbarui</button>
                @if(config('checkout.staging') && app()->environment('local','testing','staging'))
                    <button class="kc-action" name="lanjut" value="1" data-pending-label="Membuka pesanan…">Lanjut pesan</button>
                @endif
                <small class="bk-bag-state" role="status" data-quantity-state></small>
            </form>
            <details class="bk-remove"><summary>Hapus</summary><p>Hapus {{ $p->nama }} dari keranjang?</p>
                <form data-pending="Menghapus…" method="post" action="{{ route('cart.remove',$p->id) }}">@csrf @method('delete')<button class="bk-secondary">Ya, hapus</button></form>
            </details></div>
        </article>
    @empty<p class="kc-empty">Keranjang masih kosong.</p>@endforelse
    <a class="kc-discover" href="{{ route('catalog.index') }}">Kembali ke katalog</a>
</div></section>
@endsection

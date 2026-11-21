@extends('layouts.customer')
@section('title','Riwayat pesanan · SIBERKAH')
@section('content')
<section class="container-siberkah py-8 bk-order-list" aria-labelledby="history-title">
    <header><p class="kc-kicker">Berkah Kreatif · Pelanggan</p><h1 id="history-title">Riwayat pesanan</h1><p>Pilih nomor pesanan untuk melihat perkembangan dan rincian pembayaran.</p></header>
    <nav class="bk-history-filters" aria-label="Kelompok pesanan">
        @foreach([''=>'Semua','aktif'=>'Aktif','arsip'=>'Arsip'] as $key=>$label)
            <a @if(($group ?? '') === $key) aria-current="page" @endif href="{{ route('pesanan.index', $key ? ['kelompok'=>$key] : []) }}#daftar-pesanan">{{ $label }}</a>
        @endforeach
    </nav>
    <div id="daftar-pesanan" tabindex="-1">
        <p class="bk-history-count">{{ $pesanan->total() }} pesanan · {{ ['aktif'=>'Masih berjalan','arsip'=>'Selesai, dibatalkan, atau kedaluwarsa'][$group] ?? 'Semua status' }}</p>
        @forelse($pesanan as $p)
            <a id="pesanan-{{ $p->id }}" class="bk-history-card" href="{{ route('pesanan.show',array_filter(['nomor'=>$p->nomor,'kelompok'=>$group,'page'=>$pesanan->currentPage()>1?$pesanan->currentPage():null])) }}">
                <div><strong>{{ $p->nomor }}</strong><span class="bk-order-status">{{ str_replace('_',' ',$p->status) }}</span></div>
                <p>{{ $p->cara_serah === 'ambil' ? 'Ambil di toko' : 'Pengantaran' }}@if($p->tanggal_serah) · {{ $p->tanggal_serah->locale('id')->translatedFormat('d F Y') }}@endif</p>
                <div><span>Total pesanan <b>Rp{{ number_format($p->total,0,',','.') }}</b></span><span aria-hidden="true">Lihat rincian ↗</span></div>
            </a>
        @empty
            <div class="kc-empty">
                @if($pesanan->currentPage()>1)<p>Tidak ada pesanan di halaman ini.</p><a class="bk-secondary" href="{{ route('pesanan.index',array_filter(['kelompok'=>$group])) }}#daftar-pesanan">Ke halaman pertama</a>
                @elseif($group)<p>Belum ada pesanan {{ $group==='arsip'?'di arsip':'aktif' }}.</p><a class="bk-secondary" href="{{ route('pesanan.index') }}#daftar-pesanan">Lihat semua pesanan</a>
                @else<p>Belum ada pesanan.</p><a class="bk-secondary" href="{{ route('catalog.index') }}">Jelajahi katalog</a>@endif
            </div>
        @endforelse
        {{ $pesanan->links('partials.pagination') }}
    </div>
</section>
@endsection

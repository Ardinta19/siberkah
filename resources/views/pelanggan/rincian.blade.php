@extends('layouts.customer')
@section('content')
@section('refresh_pesanan', '60')
<section class="container-siberkah py-8">
    <h1 class="text-2xl font-bold">Pesanan {{ $pesanan->nomor }}</h1>
    <p>{{ str_replace('_', ' ', $pesanan->status) }}</p>
    <ul class="my-4">@foreach($pesanan->items as $item)<li>{{ $item->jumlah }} × {{ $item->nama_produk }}</li>@endforeach</ul>
    @if($pesanan->tunai_saat_serah && !in_array($pesanan->status, ['kedaluwarsa', 'dibatalkan']))
        @if($pesanan->status === 'menunggu_pembayaran')<p>Bayar DP terlebih dahulu. Sisa tagihan dibayar tunai saat serah.</p>
        @elseif(in_array($pesanan->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap']))<p>Bayar tunai saat {{ $pesanan->cara_serah === 'ambil' ? 'ambil di toko' : 'pesanan diantar' }}: Rp{{ number_format($pesanan->saldo, 0, ',', '.') }}.</p>@endif
    @endif
    <h2>Riwayat status</h2>
    <ol>@foreach($riwayat as $perubahan)<li>{{ $perubahan->dibuat_pada }} WIB · {{ str_replace('_', ' ', $perubahan->status_baru) }}</li>@endforeach</ol>
    <strong>Total Rp{{ number_format($pesanan->total, 0, ',', '.') }}</strong>
    @if($link = \App\Services\WhatsAppLinks::order($pesanan, false))
        <a href="{{ $link }}" rel="noopener noreferrer" target="_blank">Tanyakan pesanan ini ke Admin</a>
    @endif
</section>
@endsection

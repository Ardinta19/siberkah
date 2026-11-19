@extends('layouts.customer')
@section('content')
<section class="container-siberkah py-8">
    <h1 class="text-2xl font-bold">Pesanan {{ $pesanan->nomor }}</h1>
    <p>{{ str_replace('_', ' ', $pesanan->status) }}</p>
    <ul class="my-4">@foreach($pesanan->items as $item)<li>{{ $item->jumlah }} × {{ $item->nama_produk }}</li>@endforeach</ul>
    <strong>Total Rp{{ number_format($pesanan->total, 0, ',', '.') }}</strong>
    @if($link = \App\Services\WhatsAppLinks::order($pesanan, false))
        <a href="{{ $link }}" rel="noopener noreferrer" target="_blank">Tanyakan pesanan ini ke Admin</a>
    @endif
</section>
@endsection

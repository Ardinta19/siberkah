@extends('layouts.customer')
@section('content')
@if(!$papan || !in_array($pesanan->status,['menunggu_pembayaran','dikonfirmasi']))
@section('refresh_pesanan', '60')
@endif
<section class="container-siberkah py-8">
    <h1 class="text-2xl font-bold">Pesanan {{ $pesanan->nomor }}</h1>
    <p>{{ str_replace('_', ' ', $pesanan->status) }}</p>
    <ul class="my-4">@foreach($pesanan->items as $item)<li>{{ $item->jumlah }} × {{ $item->nama_produk }}</li>@endforeach</ul>
    @if($pesanan->tunai_saat_serah && !in_array($pesanan->status, ['kedaluwarsa', 'dibatalkan']))
        @if($pesanan->status === 'menunggu_pembayaran')<p>Bayar DP terlebih dahulu. Sisa tagihan dibayar tunai saat serah.</p>
        @elseif(in_array($pesanan->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap']))<p>Bayar tunai saat {{ $pesanan->cara_serah === 'ambil' ? 'ambil di toko' : 'pesanan diantar' }}: Rp{{ number_format($pesanan->saldo, 0, ',', '.') }}.</p>@endif
    @endif
    @if($papan)
        <h2>Teks papan</h2>
        @if(in_array($pesanan->status,['menunggu_pembayaran','dikonfirmasi']) && config('checkout.staging'))
            <form id="teks-papan" method="post" action="{{ route('pesanan.teks-papan',$pesanan->nomor) }}">@csrf @method('put')
                @foreach(['judul'=>25,'penerima'=>45,'pengirim'=>40] as $field=>$limit)
                    <label>{{ ucfirst($field) }} <input required name="{{ $field }}" maxlength="{{ $limit }}" value="{{ old($field,$papan->$field) }}"></label>
                @endforeach
                <button>Simpan teks</button><pre aria-label="Pratinjau teks papan" id="preview-papan"></pre>
            </form>
            <script>const board=document.getElementById('teks-papan');function previewBoard(){document.getElementById('preview-papan').textContent=['judul','penerima','pengirim'].map(k=>board.elements[k].value).join('\n')}board.addEventListener('input',previewBoard);previewBoard();</script>
        @else<p>{{ $papan->judul }} · {{ $papan->penerima }} · {{ $papan->pengirim }} (terkunci)</p>@endif
    @endif
    <h2>Riwayat status</h2>
    <ol>@foreach($riwayat as $perubahan)<li>{{ $perubahan->dibuat_pada }} WIB · {{ str_replace('_', ' ', $perubahan->status_baru) }}</li>@endforeach</ol>
    <strong>Total Rp{{ number_format($pesanan->total, 0, ',', '.') }}</strong>
    @if(config('checkout.staging') && in_array($pesanan->asal, ['situs','admin']))
        <a href="{{ route('payment.show', $pesanan->nomor) }}">Lihat tagihan dan petunjuk pembayaran</a>
    @endif
    @if(config('checkout.staging') && in_array($pesanan->asal, ['situs','admin']))
        @if($pesanan->status === 'menunggu_pembayaran')
            <form method="post" action="{{ route('pesanan.cancel',$pesanan->nomor) }}">@csrf<button>Batalkan pesanan</button></form>
        @elseif(in_array($pesanan->status,['dikonfirmasi','sedang_diproduksi','siap']))
            <form method="post" action="{{ route('pesanan.request-cancellation',$pesanan->nomor) }}">@csrf<label>Alasan pembatalan <input required maxlength="300" name="alasan"></label><button>Ajukan pembatalan</button></form>
        @endif
    @endif
    @if($link = \App\Services\WhatsAppLinks::order($pesanan, false))
        <a href="{{ $link }}" rel="noopener noreferrer" target="_blank">Tanyakan pesanan ini ke Admin</a>
    @endif
</section>
@endsection

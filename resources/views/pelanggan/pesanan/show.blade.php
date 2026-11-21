@extends('layouts.customer')
@section('title', 'Pesanan '.$pesanan->nomor)
@section('content')
<section class="container-siberkah py-8 bk-order-detail" @if(!$papan || !in_array($pesanan->status,['menunggu_pembayaran','dikonfirmasi'])) data-order-refresh @endif>
    <a href="{{ route('pesanan.index') }}" class="bk-order-back">← Riwayat pesanan</a>
    @if($errors->any())<div class="bk-notice" role="alert"><strong>Perubahan belum tersimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($notice=session('status') ?? session('success'))<p role="status" class="bk-notice">{{ $notice }}</p>@endif
    <h1 class="text-2xl font-bold">Pesanan {{ $pesanan->nomor }}</h1>
    <p class="bk-order-status">{{ str_replace('_', ' ', $pesanan->status) }}</p>
    <p>{{ $pesanan->cara_serah === 'ambil' ? 'Ambil di toko' : 'Pengantaran' }}@if($pesanan->tanggal_serah) · {{ $pesanan->tanggal_serah->locale('id')->translatedFormat('d F Y') }}@endif</p>
    <ul class="my-4">@foreach($pesanan->items as $item)<li>{{ $item->jumlah }} × {{ $item->nama_produk }}</li>@endforeach</ul>
    @if($pesanan->tunai_saat_serah && !in_array($pesanan->status, ['kedaluwarsa', 'dibatalkan']))
        @if($pesanan->status === 'menunggu_pembayaran')<p>Bayar DP terlebih dahulu. Sisa tagihan dibayar tunai saat serah.</p>
        @elseif(in_array($pesanan->status, ['dikonfirmasi', 'sedang_diproduksi', 'siap']))<p>Bayar tunai saat {{ $pesanan->cara_serah === 'ambil' ? 'ambil di toko' : 'pesanan diantar' }}: Rp{{ number_format($pesanan->saldo, 0, ',', '.') }}.</p>@endif
    @endif
    @if($papan)
        <h2>Teks papan</h2>
        @if(in_array($pesanan->status,['menunggu_pembayaran','dikonfirmasi']) && config('checkout.staging'))
            <form data-pending="Menyimpan teks…" id="teks-papan" method="post" action="{{ route('pesanan.teks-papan',$pesanan->nomor) }}">@csrf @method('put')
                @foreach(['judul'=>25,'penerima'=>45,'pengirim'=>40] as $field=>$limit)
                    <label>{{ ucfirst($field) }} <input required name="{{ $field }}" maxlength="{{ $limit }}" value="{{ old($field,$papan->$field) }}"></label>
                @endforeach
                <button>Simpan teks</button><pre aria-label="Pratinjau teks papan" id="preview-papan"></pre>
            </form>
            <script>const board=document.getElementById('teks-papan');function previewBoard(){document.getElementById('preview-papan').textContent=['judul','penerima','pengirim'].map(k=>board.elements[k].value).join('\n')}board.addEventListener('input',previewBoard);previewBoard();</script>
        @else<p>{{ $papan->judul }} · {{ $papan->penerima }} · {{ $papan->pengirim }} (terkunci)</p>@endif
    @endif
    <h2>Riwayat status</h2>
    <ol class="bk-order-timeline">@foreach($riwayat as $perubahan)<li @if($loop->last) aria-current="step" @endif>{{ $perubahan->dibuat_pada }} WIB · {{ str_replace('_', ' ', $perubahan->status_baru) }}</li>@endforeach</ol>
    <strong>Total Rp{{ number_format($pesanan->total, 0, ',', '.') }}</strong>
    @if(config('checkout.staging') && in_array($pesanan->asal, ['situs','admin']))
        <a href="{{ route('payment.show', $pesanan->nomor) }}">Lihat tagihan dan petunjuk pembayaran</a>
    @endif
    @if(config('checkout.staging') && in_array($pesanan->asal, ['situs','admin']))
        @if($permohonan)
            <section class="bk-cancel-record" aria-labelledby="cancel-title"><h2 id="cancel-title">Permohonan pembatalan</h2>
                <p>Status permohonan: <strong>{{ ucfirst($permohonan->status) }}</strong></p><p>Alasan Anda: {{ $permohonan->alasan }}</p>
                <p>Diajukan {{ $permohonan->diajukan_pada }} WIB.</p>
                @if($permohonan->diputuskan_pada)<p>Diputuskan {{ $permohonan->diputuskan_pada }} WIB. {{ $permohonan->catatan_keputusan }}</p>
                @else<p>Menunggu pemeriksaan Admin. Pesanan belum dibatalkan.</p>@endif
            </section>
        @elseif($pesanan->status === 'menunggu_pembayaran')
            <details class="bk-confirm"><summary>Batalkan pesanan</summary><p>Batalkan pesanan {{ $pesanan->nomor }}? Reservasi yang ditahan akan dilepas.</p><form data-pending="Membatalkan…" method="post" action="{{ route('pesanan.cancel',$pesanan->nomor) }}">@csrf<button>Ya, batalkan {{ $pesanan->nomor }}</button></form></details>
        @elseif(in_array($pesanan->status,['dikonfirmasi','sedang_diproduksi','siap']) && ($pesanan->jumlah_dibayar > 0 || $pesanan->tunai_saat_serah))
            <p>Permohonan untuk {{ $pesanan->nomor }} akan ditinjau Admin; status pesanan tidak langsung berubah.</p><form data-pending="Mengirim permohonan…" method="post" action="{{ route('pesanan.request-cancellation',$pesanan->nomor) }}">@csrf<label>Alasan pembatalan <input required maxlength="300" name="alasan" value="{{ old('alasan') }}"></label><button>Ajukan pembatalan</button></form>
        @endif
    @endif
    @if($link = \App\Services\WhatsAppLinks::order($pesanan, false))
        <a href="{{ $link }}" rel="noopener noreferrer" target="_blank">Tanyakan pesanan ini ke Admin</a>
    @endif
</section>
@endsection

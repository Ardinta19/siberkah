@extends('layouts.admin')
@section('title','Periksa '.$order->nomor.' · SIBERKAH')
@section('content')
<section class="bk-owner-list">@if(request('asal')==='pembatalan')<x-back-link href="{{ route('admin.permohonan-batal.index',array_intersect_key($context,['page'=>true])) }}#permohonan-{{ $order->id }}">Kembali ke permohonan pembatalan</x-back-link>@else<x-back-link href="{{ route('admin.pesanan.index',$context) }}#pesanan-{{ $order->id }}">Kembali ke daftar pesanan</x-back-link>@endif
<header><p class="kc-kicker">Operasional · Rincian pesanan</p><h1>{{ $order->nomor }}</h1><span class="bk-order-status">{{ str_replace('_',' ',$order->status) }}</span></header>
<p>Pemesan: {{ $order->nama_pemesan }} · {{ $order->cara_serah==='ambil' ? 'Ambil di toko' : 'Pengantaran' }} · {{ $order->tanggal_serah?->format('d/m/Y') }}</p>
<nav class="bk-history-filters" aria-label="Bagian rincian pesanan">@if($board)<a href="#teks-papan">Teks papan</a>@endif<a href="#order-cost-title">Biaya</a><a href="#pembayaran">Pembayaran</a>@if($extension)<a href="#perpanjangan">Durasi sewa</a>@endif<a href="#riwayat-status">Riwayat status</a></nav>
@if($board)@include('admin.pesanan.papan')@endif
@include('partials.rincian-biaya-pesanan',['pesanan'=>$order])
@if(config('checkout.staging') && app()->environment('local','testing','staging'))
@foreach($order->items->whereNotNull('unit_sewa_id') as $line)<a id="unit-item-{{ $line->id }}" class="bk-secondary" href="{{ route('admin.pesanan.unit',['id'=>$order->id,'item'=>$line->id]+$context+(request('asal')==='pembatalan'?['asal'=>'pembatalan']:[])) }}">Periksa unit · {{ $line->nama_produk }} · item #{{ $line->id }}</a>@endforeach
@endif
@if($extension)@include('admin.pesanan.perpanjangan')@endif
<h2 id="pembayaran">Posisi pembayaran</h2><p>Total Rp{{ number_format($order->total,0,',','.') }} · Saldo Rp{{ number_format($order->saldo,0,',','.') }}</p>
@forelse($order->pembayaran as $bill)<article class="bk-task"><h3>{{ ucfirst($bill->getRawOriginal('jenis')) }}</h3><p>Status: {{ str_replace('_',' ',$bill->getRawOriginal('status')) }} · Pokok Rp{{ number_format($bill->tagihan,0,',','.') }}</p></article>@empty<p>Belum ada baris pembayaran.</p>@endforelse
@if($refund)@include('admin.pesanan.refund')@endif
@if(config('checkout.staging') && app()->environment('local','testing','staging'))<a class="bk-secondary" href="{{ route('kas-antar.index',['tanggal'=>now('Asia/Jakarta')->toDateString()]) }}">Buka antrean verifikasi dan setoran hari ini</a>@endif
<details class="bk-confirm" id="riwayat-status"><summary>Riwayat status · {{ $history->count() }} catatan</summary>
<p>Terbaru lebih dahulu. Membuka riwayat tidak mengubah status pesanan.</p><ol>@forelse($history as $event)<li class="bk-task"><strong>{{ ucfirst(str_replace('_',' ',$event->status_baru)) }}</strong>
@if($event->status_lama)<p>Sebelumnya: {{ str_replace('_',' ',$event->status_lama) }}</p>@endif<p>{{ $event->dibuat_pada?->format('d/m/Y H:i') ?? 'Waktu tidak tercatat' }}@if($event->dibuat_pada) WIB @endif · {{ ucfirst($event->pelaku) }} @if($event->pengguna_id)#{{ $event->pengguna_id }}@endif @if($event->kode_transisi) · {{ $event->kode_transisi }}@endif</p>@if($event->catatan)<p>{{ $event->catatan }}</p>@endif</li>@empty<li>Belum ada riwayat status.</li>@endforelse</ol></details>
@if($order->tautan_whatsapp)<a class="bk-secondary" href="{{ $order->tautan_whatsapp }}" target="_blank" rel="noopener noreferrer">Hubungi pemesan (tab baru)</a>@endif</section>
@endsection

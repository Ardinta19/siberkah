@extends('layouts.admin')
@section('title','Periksa '.$order->nomor.' · SIBERKAH')
@section('content')
<section class="bk-owner-list">@if(request('asal')==='pembatalan')<x-back-link href="{{ route('admin.permohonan-batal.index',array_intersect_key($context,['page'=>true])) }}#permohonan-{{ $order->id }}">Kembali ke permohonan pembatalan</x-back-link>@else<x-back-link href="{{ route('admin.pesanan.index',$context) }}#pesanan-{{ $order->id }}">Kembali ke daftar pesanan</x-back-link>@endif
<header><p class="kc-kicker">Operasional · Rincian pesanan</p><h1>{{ $order->nomor }}</h1><span class="bk-order-status">{{ str_replace('_',' ',$order->status) }}</span></header>
<p>Pemesan: {{ $order->nama_pemesan }} · {{ $order->cara_serah==='ambil' ? 'Ambil di toko' : 'Pengantaran' }} · {{ $order->tanggal_serah?->format('d/m/Y') }}</p>
@include('partials.rincian-biaya-pesanan',['pesanan'=>$order])
<h2>Posisi pembayaran</h2><p>Total Rp{{ number_format($order->total,0,',','.') }} · Saldo Rp{{ number_format($order->saldo,0,',','.') }}</p>
@forelse($order->pembayaran as $bill)<article class="bk-task"><h3>{{ ucfirst($bill->getRawOriginal('jenis')) }}</h3><p>Status: {{ str_replace('_',' ',$bill->getRawOriginal('status')) }} · Pokok Rp{{ number_format($bill->tagihan,0,',','.') }}</p></article>@empty<p>Belum ada baris pembayaran.</p>@endforelse
@if($refund)@include('admin.pesanan.refund')@endif
@if(config('checkout.staging') && app()->environment('local','testing','staging'))<a class="bk-secondary" href="{{ route('kas-antar.index',['tanggal'=>now('Asia/Jakarta')->toDateString()]) }}">Buka antrean verifikasi dan setoran hari ini</a>@endif
@if($order->tautan_whatsapp)<a class="bk-secondary" href="{{ $order->tautan_whatsapp }}" target="_blank" rel="noopener noreferrer">Hubungi pemesan (tab baru)</a>@endif</section>
@endsection

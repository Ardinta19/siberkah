@extends('layouts.admin')
@section('title','Riwayat setoran · '.$receipt->nomor)
@section('content')
<section class="bk-owner-list">
<x-back-link :href="route('kas-antar.index',$back).'#setoran-'.$id">Kembali ke setoran ini</x-back-link>
<header><p class="kc-kicker">Kas antar · {{ $receipt->nomor }}</p><h1>Riwayat hitung dan koreksi</h1>
<p>Petugas antar #{{ $receipt->pengguna_id }} menerima Rp{{ number_format($receipt->nominal_diterima,0,',','.') }} pada {{ $receipt->diterima_pada }} WIB.</p></header>
<p>{{ $entries->total() }} pencatatan, terbaru lebih dahulu. Total setelah hitung adalah jumlah kumulatif yang disetor, bukan pembayaran pelanggan baru.</p>
@forelse($entries as $entry)
@php($delta = (int)$entry->sesudah - (int)$entry->sebelum)
<article class="bk-task"><header><h2>Hitung #{{ $entries->total()-$entries->firstItem()+1-$loop->index }}</h2><p>{{ $entry->dicatat_pada }} WIB · Kasir #{{ $entry->kasir_id }}</p></header>
<dl><dt>Sebelum hitung</dt><dd>Rp{{ number_format($entry->sebelum,0,',','.') }}</dd><dt>Setelah hitung</dt><dd>Rp{{ number_format($entry->sesudah,0,',','.') }}</dd><dt>Perubahan</dt><dd>{{ $delta<0?'−':($delta>0?'+':'') }}Rp{{ number_format(abs($delta),0,',','.') }}</dd></dl>
<p>Catatan: {{ $entry->catatan ?: 'Tidak ada catatan.' }}</p></article>
@empty<p>Kasir belum mencatat hasil hitung setoran ini.</p>@endforelse
{{ $entries->links('partials.pagination') }}</section>
@endsection

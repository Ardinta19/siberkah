@extends('layouts.admin')
@section('title','Permohonan pembatalan · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pesanan.index')">Kembali ke daftar pesanan</x-back-link>
<header><p class="kc-kicker">Operasional · Pemeriksaan</p><h1>Permohonan pembatalan</h1><p>Permohonan tertua ditampilkan lebih dahulu. Belum ada perubahan pada pesanan sampai keputusan disimpan.</p></header>
@if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
<p>{{ $rows->total() }} permohonan menunggu pemeriksaan.</p>
@forelse($rows as $r)<article id="permohonan-{{ $r->pesanan_id }}" class="bk-task"><header><h2>{{ $r->nomor }}</h2><span class="bk-order-status">{{ str_replace('_',' ',$r->status_pesanan) }}</span></header>
<p>Diajukan {{ $r->diajukan_pada }} WIB</p><p>Alasan pelanggan: {{ $r->alasan }}</p>
<a class="bk-secondary" href="{{ route('admin.pesanan.show',['id'=>$r->pesanan_id,'asal'=>'pembatalan','page'=>$rows->currentPage()]) }}">Periksa rincian {{ $r->nomor }}</a>
@foreach(['approve'=>'Setujui','reject'=>'Tolak'] as $action=>$label)
@php($retry = old('baris')==$r->pesanan_id && old('keputusan')===$action)
<details class="bk-confirm" @if($retry && $errors->any()) open @endif><summary>{{ $label }} permohonan {{ $r->nomor }}</summary>
<p>{{ $action==='approve' ? 'Pesanan akan dibatalkan. Sistem menghitung kewajiban refund sesuai aturan; tidak ada transfer otomatis.' : 'Pesanan tetap berjalan. Pelanggan dapat melihat alasan keputusan ini.' }}</p>
<form method="post" class="bk-product-editor grid gap-3 p-4" action="{{ route('admin.pesanan.'.$action.'-cancellation',$r->pesanan_id) }}" data-pending="Menyimpan keputusan…">@csrf
<input type="hidden" name="asal" value="antrean"><input type="hidden" name="baris" value="{{ $r->pesanan_id }}"><input type="hidden" name="keputusan" value="{{ $action }}">
<label>Catatan keputusan untuk pelanggan <textarea name="alasan" maxlength="300" required>{{ $retry ? old('alasan') : '' }}</textarea></label>
@if($retry)@error('alasan')<p role="alert">{{ $message }}</p>@enderror @endif<button class="bk-secondary">Ya, {{ strtolower($label) }} permohonan {{ $r->nomor }}</button></form></details>
@endforeach</article>@empty<p>Tidak ada permohonan pada halaman ini.</p>@endforelse
{{ $rows->links('partials.pagination') }}</section>
@endsection

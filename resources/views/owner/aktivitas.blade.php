@extends('layouts.admin')
@section('title','Riwayat aktivitas · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="$rates ? route('admin.pengaturan.index') : route('dashboard.hari-ini')">{{ $rates ? 'Kembali ke pengaturan' : 'Kembali ke dasbor' }}</x-back-link>
<header><p class="kc-kicker">Owner · Audit</p><h1>Riwayat aktivitas</h1><p>Catatan baca-saja, dari yang terbaru.</p></header>
<nav class="bk-history-filters" aria-label="Kelompok aktivitas">@foreach([''=>'Semua aktivitas','tarif'=>'Perubahan tarif'] as $key=>$label)<a @if(($rates?'tarif':'')===$key) aria-current="page" @endif href="{{ route('admin.aktivitas.index',array_filter(['kelompok'=>$key])) }}">{{ $label }}</a>@endforeach</nav>
<p>{{ $rows->total() }} catatan</p>
@forelse($rows as $entry)<article class="bk-task"><h2>{{ ucfirst(str_replace('_',' ',$entry->aksi)) }}</h2><p>{{ $entry->dibuat_pada }} WIB · Catatan #{{ $entry->id }}</p>
@if($entry->subjek_tabel)<p>{{ str_replace('_',' ',$entry->subjek_tabel) }} #{{ $entry->subjek_id }}</p>@endif
@if(in_array($entry->aksi,['ubah_tarif_zona','ubah_tarif_kerusakan'],true))
@php($key=$entry->aksi==='ubah_tarif_zona'?'ongkir':'tarif')
@if(isset($entry->sebelum[$key],$entry->sesudah[$key]))<p>Sebelum: Rp{{ number_format($entry->sebelum[$key],0,',','.') }} · Sesudah: Rp{{ number_format($entry->sesudah[$key],0,',','.') }}</p>@endif
@endif</article>@empty<p>Tidak ada catatan pada halaman ini.</p>@endforelse
{{ $rows->links('partials.pagination') }}</section>
@endsection

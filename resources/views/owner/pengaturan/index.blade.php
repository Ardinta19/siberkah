@extends('layouts.admin')
@section('title','Pengaturan bisnis · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link><header><p class="kc-kicker">Owner · Operasional</p><h1>Pengaturan bisnis</h1><p>Simpan satu aturan pada satu waktu. Batas dan satuan tercantum di setiap aturan.</p></header>
@if(config('checkout.staging') && app()->environment('local','staging','testing'))<a class="bk-secondary" href="{{ route('tujuan.index') }}">Kelola tujuan pembayaran</a>@endif
@if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
@foreach($pengaturan as $key=>$s)<article id="aturan-{{ $key }}" class="bk-task"><h2>{{ ucfirst(str_replace('_',' ',$key)) }}</h2><p>Nilai aktif: {{ $s['nilai'] }} · Bawaan: {{ $s['bawaan'] }}</p>
<form action="{{ route('admin.pengaturan.update') }}" method="post" class="bk-product-editor grid gap-3 p-4" data-pending="Menyimpan aturan…">@csrf<input type="hidden" name="kunci" value="{{ $key }}"><input type="hidden" name="sebelum" value="{{ $s['nilai'] }}">
<label>Nilai {{ str_replace('_',' ',$key) }} <input required type="number" name="nilai" min="{{ $s['min'] }}" max="{{ $s['maks'] }}" value="{{ $s['nilai'] }}"></label><small>Batas {{ $s['min'] }}–{{ $s['maks'] }}</small>
@if(old('kunci')===$key)@error('nilai')<p role="alert">{{ $message }}</p>@enderror @endif<button class="btn-primary">Simpan aturan</button></form></article>@endforeach
<h2>Zona antar</h2>@forelse($zona as $z)
@include('owner.pengaturan.rate',['row'=>$z,'field'=>'ongkir','endpoint'=>'pengaturan.ongkir','detail'=>'Hingga '.$z->jarak_maks_km.' km · '.($z->aktif ? 'Aktif' : 'Nonaktif')])
@empty<p>Belum ada zona antar.</p>@endforelse
<h2>Tarif kerusakan</h2>@forelse($tarif_kerusakan as $t)
@include('owner.pengaturan.rate',['row'=>$t,'field'=>'tarif','endpoint'=>'pengaturan.tarif-kerusakan','detail'=>'Per '.$t->satuan.' · '.$t->kode])
@empty<p>Belum ada tarif kerusakan.</p>@endforelse
<a class="bk-secondary" href="{{ route('admin.aktivitas.index',['kelompok'=>'tarif']) }}">Lihat riwayat perubahan tarif</a></section>
@endsection

@extends('layouts.admin')
@section('title','Kalender operasional · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pengaturan.index')">Kembali ke pengaturan</x-back-link>
<header><p class="kc-kicker">Owner · Operasional</p><h1>Kalender dan kapasitas produksi</h1><p>Pilih tanggal serah untuk memeriksa kapasitas buket custom dan blokir pemesanan.</p></header>
<form class="bk-product-editor grid gap-3 p-4" method="get"><label>Tanggal serah (WIB)<input type="date" name="tanggal" required value="{{ $day }}"></label><button class="bk-secondary">Tampilkan tanggal</button></form>
@if(session('success'))<p class="bk-notice" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert" class="bk-notice">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>@endif
@if(session('peringatan_pesanan_aktif'))<p role="alert">Blokir tidak membatalkan pesanan aktif. Periksa: {{ implode(', ',session('peringatan_pesanan_aktif')) }}.</p>@endif
@php($limit = $capacity->batas ?? 15)
@php($used = $capacity->terpakai ?? 0)
@php($editable = $day >= now('Asia/Jakarta')->toDateString())
<article class="bk-task"><h2>Buket custom · {{ $day }}</h2><p>Terpakai {{ $used }} dari {{ $limit }} · Sisa kuota {{ max(0,$limit-$used) }}</p>
@if($editable)<form class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('admin.pengaturan.manage-production-capacity') }}" data-pending="Menyimpan kapasitas…">@csrf
<input type="hidden" name="asal" value="kalender"><input type="hidden" name="tanggal" value="{{ $day }}"><input type="hidden" name="kelompok_kapasitas" value="buket_custom"><input type="hidden" name="sebelum" value="{{ $limit }}">
<label>Batas buket custom <input type="number" name="batas" required min="{{ $used }}" max="15" value="{{ $limit }}"></label><small>Maksimal 15. Batas tidak boleh di bawah jumlah yang sudah dipesan.</small><button class="btn-primary">Simpan kapasitas</button></form>@else<p>Tanggal lampau hanya dapat dilihat.</p>@endif</article>
<h2>Blokir pada tanggal ini</h2>@forelse($blocks as $block)<article class="bk-task"><h3>{{ $block->produk_id ? ($block->produk?->nama ?? 'Produk #'.$block->produk_id) : 'Seluruh toko' }}</h3><p>{{ $block->alasan }}</p>
@if($editable)<details class="bk-confirm" @if(old('blokir_id')==$block->id) open @endif><summary>Buka blokir {{ $block->produk_id ? ($block->produk?->nama ?? 'produk') : 'seluruh toko' }}</summary>
<p>Hanya blokir #{{ $block->id }} pada {{ $day }} yang dilepas. Ini tidak mengubah pesanan, kuota, atau ketersediaan unit fisik.</p>
<form class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('admin.pengaturan.kalender.buka',$block->id) }}" data-pending="Membuka blokir…">@csrf @method('delete')
<input name="tanggal" type="hidden" value="{{ $day }}"><input name="blokir_id" type="hidden" value="{{ $block->id }}">
<label>Alasan membuka blokir <input name="alasan_buka" required maxlength="160" value="{{ old('blokir_id')==$block->id ? old('alasan_buka') : '' }}"></label>
<label><input type="checkbox" name="konfirmasi" required value="1"> Saya sudah memeriksa tanggal dan cakupan.</label><button class="bk-secondary">Ya, buka blokir ini</button></form></details>@endif</article>@empty<p>Belum ada blokir pada tanggal ini.</p>@endforelse
@if($editable)@foreach(['produk'=>'produk tertentu','toko'=>'seluruh toko'] as $scope=>$label)
<details class="bk-confirm"><summary>Blokir {{ $label }}</summary><p>Tanggal {{ $day }}. Pesanan yang sudah ada tidak otomatis dibatalkan.</p><form class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('admin.pengaturan.manage-calendar-blocks') }}" data-pending="Mencatat blokir…">@csrf
<input type="hidden" name="tanggal" value="{{ $day }}"><input type="hidden" name="asal" value="kalender"><input type="hidden" name="cakupan" value="{{ $scope }}">
@if($scope==='produk')<label>Produk aktif <select required name="produk_id"><option value="">Pilih produk</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->nama }}</option>@endforeach</select></label>@endif
<label>Alasan blokir <input required name="alasan" maxlength="160"></label><label><input type="checkbox" required name="konfirmasi" value="1"> Saya mengonfirmasi blokir {{ $label }} pada {{ $day }}.</label><button class="btn-primary" @disabled($scope==='produk' && $products->isEmpty())>Simpan blokir {{ $label }}</button></form></details>
@endforeach @endif</section>
@endsection

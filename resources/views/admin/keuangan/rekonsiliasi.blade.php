@extends('layouts.admin')
@section('title','Rekonsiliasi mutasi bank · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('kas-antar.index',['tanggal'=>now('Asia/Jakarta')->toDateString()])">Kembali ke pembayaran dan setoran hari ini</x-back-link>
<header><p class="kc-kicker">Operasional · Bank</p><h1>Rekonsiliasi mutasi bank</h1><p>Mutasi ini belum dikreditkan sebagai pembayaran pesanan. Catat pengembalian hanya setelah memeriksa mutasi keluar di bank. Formulir ini tidak mengirim uang.</p></header>
<nav class="bk-history-filters" aria-label="Status rekonsiliasi">@foreach([''=>'Perlu diperiksa','dikembalikan'=>'Sudah dikembalikan'] as $key=>$label)<a @if(($history?'dikembalikan':'')===$key) aria-current="page" @endif href="{{ route('admin.rekonsiliasi.index',array_filter(['kelompok'=>$key])) }}">{{ $label }}</a>@endforeach</nav>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ $rows->total() }} mutasi pada kelompok ini.</p>
@forelse($rows as $r)<article class="bk-task"><header><h2>{{ $r->nomor }}</h2><span class="bk-order-status">Mutasi #{{ $r->id }}</span></header>
<p>Referensi masuk: {{ $r->referensi }} · Rp{{ number_format($r->nominal,0,',','.') }}</p><p>{{ $r->dicatat_pada }} WIB · {{ $r->alasan }}</p>
@if($history)<h3>Pengembalian tercatat</h3><p>Referensi keluar: {{ $r->referensi_keluar }} · {{ $r->waktu_keluar }} WIB</p><p>{{ $r->alasan_keluar }}</p>
@else<details class="bk-confirm" @if(old('mutasi')==$r->id && $errors->any()) open @endif><summary>Catat mutasi keluar untuk {{ $r->referensi }}</summary>
<form method="post" action="{{ route('admin.rekonsiliasi.pengembalian',$r->id) }}" class="bk-product-editor grid gap-3 p-4" data-ledger>@csrf
<input type="hidden" name="asal" value="rekonsiliasi"><input type="hidden" name="mutasi" value="{{ $r->id }}">
@if(old('mutasi')==$r->id && $errors->any())<ul role="alert">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>@endif
<label>Referensi keluar aktual <input name="referensi_keluar" required maxlength="100" autocomplete="off"></label>
<label>Nominal keluar aktual (Rp) <input type="number" name="nominal" required min="1" max="4294967295"></label><small>Harus sama dengan nominal masuk penuh. Jangan masukkan pokok tagihan saja.</small>
<label>Alasan pengembalian <textarea name="alasan" required maxlength="300"></textarea></label><label><input type="checkbox" required> Saya sudah mencocokkan referensi dan nominal mutasi keluar.</label>
<button class="btn-primary" type="submit">Catat pengembalian yang sudah diperiksa</button>@include('partials.ledger-feedback')</form></details>@endif
</article>@empty<p>{{ $history ? 'Belum ada pengembalian mutasi pada halaman ini.' : 'Tidak ada mutasi yang menunggu pemeriksaan pada halaman ini.' }}</p>@endforelse
{{ $rows->links('partials.pagination') }}</section>
@endsection

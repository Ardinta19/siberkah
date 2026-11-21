@extends('layouts.admin')
@section('title','Rekonsiliasi mutasi bank · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('kas-antar.index',['tanggal'=>now('Asia/Jakarta')->toDateString()])">Kembali ke pembayaran dan setoran hari ini</x-back-link>
<header><p class="kc-kicker">Operasional · Bank</p><h1>Rekonsiliasi mutasi bank</h1><p>Periksa mutasi yang belum cocok dan telusuri hasil keputusannya. Catat pengembalian hanya setelah memeriksa mutasi keluar di bank. Formulir ini tidak mengirim uang.</p></header>
<nav class="bk-history-filters" aria-label="Status rekonsiliasi">@foreach([''=>'Perlu diperiksa','dikreditkan'=>'Dikreditkan setelah pemeriksaan','dikembalikan'=>'Sudah dikembalikan'] as $key=>$label)<a @if($group===$key) aria-current="page" @endif href="{{ route('admin.rekonsiliasi.index',array_filter(['kelompok'=>$key,'q'=>$search],fn ($v)=>$v!=='')) }}">{{ $label }}</a>@endforeach</nav>
<form class="bk-product-editor grid gap-2 p-3" role="search" method="get" action="{{ route('admin.rekonsiliasi.index') }}#hasil-mutasi">
@if($group)<input type="hidden" name="kelompok" value="{{ $group }}">@endif
<label>Nomor pesanan atau referensi bank<input type="search" name="q" maxlength="100" value="{{ $search }}"></label>
<small>Cari pada kelompok ini. Di riwayat pengembalian, referensi keluar juga dapat dicari.</small><button class="btn-primary">Cari mutasi</button>
@if($search!=='')<a href="{{ route('admin.rekonsiliasi.index',array_filter(['kelompok'=>$group])) }}#hasil-mutasi">Hapus pencarian</a>@endif</form>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p id="hasil-mutasi" tabindex="-1">{{ $rows->total() }} mutasi pada kelompok ini.</p>
@forelse($rows as $r)<article class="bk-task"><header><h2>{{ $r->nomor }}</h2><span class="bk-order-status">Mutasi #{{ $r->id }}</span></header>
<p>Referensi masuk: {{ $r->referensi }} · Rp{{ number_format($r->nominal,0,',','.') }}</p><p>{{ $r->dicatat_pada }} WIB · {{ $r->alasan }}</p>
<p>Pokok tagihan Rp{{ number_format($r->tagihan,0,',','.') }} · Nominal dengan kode unik Rp{{ number_format($r->nominal_diharapkan,0,',','.') }}</p>
@if($r->catatan_selisih)<p>Keputusan petugas #{{ $r->peninjau }} · {{ $r->diputuskan_pada }} WIB: {{ $r->catatan_selisih }}</p>@endif
@if($credited)<h3>Mutasi sudah dikreditkan</h3><p>{{ $r->alasan==='reaktivasi_t13' ? 'Pesanan diaktifkan kembali setelah pemeriksaan ketersediaan.' : 'Selisih kode unik diterima.' }}</p>
@elseif($history)<h3>Pengembalian tercatat</h3><p>Referensi keluar: {{ $r->referensi_keluar }} · {{ $r->waktu_keluar }} WIB</p><p>{{ $r->alasan_keluar }}</p>
@else
@include('admin.keuangan.keputusan-mutasi')
<details class="bk-confirm" @if(old('mutasi')==$r->id && $errors->any()) open @endif><summary>Catat mutasi keluar untuk {{ $r->referensi }}</summary>
<form method="post" action="{{ route('admin.rekonsiliasi.pengembalian',$r->id) }}" class="bk-product-editor grid gap-3 p-4" data-ledger>@csrf
<input type="hidden" name="asal" value="rekonsiliasi"><input type="hidden" name="mutasi" value="{{ $r->id }}">
@if(old('mutasi')==$r->id && $errors->any())<ul role="alert">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>@endif
<label>Referensi keluar aktual <input name="referensi_keluar" required maxlength="100" autocomplete="off"></label>
<label>Nominal keluar aktual (Rp) <input type="number" name="nominal" required min="1" max="4294967295"></label><small>Harus sama dengan nominal masuk penuh. Jangan masukkan pokok tagihan saja.</small>
<label>Alasan pengembalian <textarea name="alasan" required maxlength="300"></textarea></label><label><input type="checkbox" required> Saya sudah mencocokkan referensi dan nominal mutasi keluar.</label>
<button class="btn-primary" type="submit">Catat pengembalian yang sudah diperiksa</button>@include('partials.ledger-feedback')</form></details>@endif
</article>@empty<p>{{ $search!=='' ? 'Tidak ada mutasi yang cocok pada halaman ini.' : (($history || $credited) ? 'Belum ada hasil pada halaman ini.' : 'Tidak ada mutasi yang menunggu pemeriksaan pada halaman ini.') }}</p>@endforelse
{{ $rows->links('partials.pagination') }}</section>
@endsection

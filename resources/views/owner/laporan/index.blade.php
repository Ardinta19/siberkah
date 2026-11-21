@extends('layouts.admin')
@section('title','Laporan arus kas · SIBERKAH')
@section('content')
<section class="bk-owner-list">
<x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<header><p class="kc-kicker">Owner · Laporan</p><h1>Arus kas bank</h1><p>Mutasi masuk dikurangi pengembalian mutasi dan refund terverifikasi. Ini bukan laba atau saldo rekening.</p></header>
<nav class="bk-history-filters" aria-label="Periode arus kas">@foreach(['harian','mingguan','bulanan'] as $p)<a @if($p===$periode) aria-current="page" @endif href="{{ route('admin.laporan.index',['period'=>$p]) }}">{{ ucfirst($p) }}</a>@endforeach</nav>
<p>{{ $mulai }} sampai {{ \Illuminate\Support\Carbon::parse($akhir_eksklusif)->subDay()->toDateString() }} (WIB)</p>
<h2>Neto bank: Rp{{ number_format($neto,0,',','.') }}</h2>
<a class="bk-secondary" href="{{ route('admin.laporan.export-csv',['period'=>$periode]) }}">Unduh CSV arus kas</a>
@forelse($harian as $day)<article class="bk-task"><h3>{{ $day['tanggal'] }}</h3><dl>@foreach(['masuk'=>'Masuk','pengembalian_mutasi'=>'Pengembalian mutasi','refund_pembatalan'=>'Refund pembatalan','neto'=>'Neto'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>Rp{{ number_format($day[$key],0,',','.') }}</dd></div>@endforeach</dl></article>@empty<p>Belum ada arus kas bank pada periode ini.</p>@endforelse
@include('owner.laporan.rentang',['awal'=>$mulai,'akhir'=>now('Asia/Jakarta')->toDateString()])
</section>
@endsection

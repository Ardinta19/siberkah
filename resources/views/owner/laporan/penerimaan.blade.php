@extends('layouts.admin')
@section('title','Penerimaan terverifikasi · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.laporan.index')">Kembali ke laporan arus kas</x-back-link><header><p class="kc-kicker">Owner · Laporan</p><h1>Penerimaan terverifikasi</h1><p>Pokok pembayaran dikurangi refund; kode unik bukan penerimaan pokok.</p></header>
@include('owner.laporan.rentang',['awal'=>$mulai])
<h2>Neto pada rentang ini: Rp{{ number_format($neto,0,',','.') }}</h2><a class="bk-secondary" href="{{ route('laporan.penerimaan.csv',compact('mulai','akhir')) }}">Unduh CSV penerimaan anonim</a>
@forelse($rincian as $row)<article class="bk-task"><header><h3>{{ $row['kategori'] }}</h3><span>{{ ucfirst($row['kanal']) }}</span></header><p>{{ $row['pesanan'] }} pesanan dalam kelompok ini</p><dl>@foreach(['pokok'=>'Pokok','refund'=>'Refund','neto'=>'Neto'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>Rp{{ number_format($row[$key],0,',','.') }}</dd></div>@endforeach</dl></article>@empty<p>Belum ada penerimaan terverifikasi pada rentang ini.</p>@endforelse</section>
@endsection

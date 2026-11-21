@extends('layouts.admin')
@section('title','Bukti inspeksi · '.$order->nomor)
@section('content')
<section class="bk-owner-list"><x-back-link href="{{ route('admin.pesanan.show',['id'=>$order->id]+$context) }}#inspeksi-sewa">Kembali ke rincian pesanan</x-back-link>
<header><p class="kc-kicker">Sewa · {{ $order->nomor }}</p><h1>Bukti serah dan kembali</h1><p>Bandingkan kondisi unit yang sama pada dua pemeriksaan. Catatan ini bukan penilaian biaya kerusakan atau keputusan penggunaan deposit.</p></header>
@if($items->count()>1)<nav aria-label="Pilih unit inspeksi" class="bk-history-filters">@foreach($items as $item)<a href="#item-{{ $item->id }}">{{ $item->kode ?? 'Item #'.$item->id }}</a>@endforeach</nav>@endif
@foreach($items as $item)<article class="bk-task" id="item-{{ $item->id }}"><header><h2>{{ $item->nama_produk }} · {{ $item->nama_varian }}</h2><p>Unit {{ $item->kode ?? 'belum ditetapkan' }} · Item #{{ $item->id }}</p></header>
<div class="bk-inspection-pair">@foreach(['serah'=>'Saat diserahkan','kembali'=>'Saat dikembalikan'] as $key=>$label)
@php($rows=$inspections->get($item->id,collect())->get($key,collect()))
<section><h3>{{ $label }}</h3>@if($event=$rows->first())<p><strong>Kondisi: {{ ucfirst($event->kondisi) }}</strong></p><p>{{ $event->dilakukan_pada }} WIB · Petugas #{{ $event->pengguna_id }}</p>
<dl>@foreach(json_decode($event->daftar_periksa ?? '[]',true) ?? [] as $part=>$value)<div><dt>{{ ucfirst(str_replace('_',' ',$part)) }}</dt><dd>{{ ucfirst($value) }}</dd></div>@endforeach</dl>
<p>Catatan: {{ $event->catatan ?? 'Tidak ada catatan.' }}</p>
@forelse($rows->whereNotNull('foto_id') as $photo)<a class="bk-secondary" href="{{ route('retur.foto',[$order->id,$photo->foto_id]) }}" target="_blank" rel="noopener noreferrer">Foto {{ $photo->urutan }} · {{ $label }} (tab baru)</a>@empty<p>Tidak ada foto pada pemeriksaan ini.</p>@endforelse
@else<p>Belum ada inspeksi pada tahap ini.</p>@endif</section>@endforeach</div></article>@endforeach</section>
@endsection

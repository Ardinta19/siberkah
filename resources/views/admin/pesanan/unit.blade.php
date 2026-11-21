@extends('layouts.admin')
@section('title','Unit sewa · '.$order->nomor)
@section('content')
<section class="bk-owner-list"><x-back-link href="{{ route('admin.pesanan.show',['id'=>$order->id]+$context) }}#unit-item-{{ $line->id }}">Kembali ke rincian pesanan</x-back-link>
<header><p class="kc-kicker">{{ $order->nomor }} · Item #{{ $line->id }}</p><h1>Unit sewa pesanan</h1><p>{{ $line->nama_produk }} · {{ $line->nama_varian }} · Tanggal serah {{ $order->tanggal_serah->format('d/m/Y') }}</p></header>
<p>Unit yang ditetapkan: <strong>{{ $current }}</strong></p><h2>Penggantian unit</h2>
@if($pending)<p>Ada permohonan pembatalan yang belum diputuskan. Penggantian tidak tersedia.</p>
@elseif($locked)<p>Penggantian hanya tersedia untuk pesanan dikonfirmasi atau siap dengan tanggal serah hari ini atau sesudahnya.</p>
@elseif($choices->isNotEmpty())<p>Pilih unit aktif dari varian yang sama. Reservasi berpindah tanpa mengubah harga pesanan. Ketersediaan diperiksa kembali saat menyimpan.</p>
<form method="post" class="bk-product-editor grid gap-3 p-4" action="{{ route('admin.pesanan.ganti-unit',$order->id) }}" data-ledger>@csrf
<input type="hidden" name="item_id" value="{{ $line->id }}"><input type="hidden" name="unit_lama_id" value="{{ $line->unit_sewa_id }}"><input type="hidden" name="kunci" value="{{ \Illuminate\Support\Str::uuid() }}">
<label>Unit pengganti <select required name="unit_baru_id"><option value="">Pilih kode unit</option>@foreach($choices as $unit)<option value="{{ $unit->id }}">{{ $unit->kode }}</option>@endforeach</select></label>
<label>Alasan penggantian <textarea required maxlength="300" name="alasan"></textarea></label>
<button class="btn-primary">Simpan penggantian unit {{ $current }}</button>@include('partials.ledger-feedback')</form>
@else<p>Tidak ada unit pengganti yang tersedia pada halaman ini.</p>@endif
@if($choices){{ $choices->links('partials.pagination') }}@endif
<h2>Riwayat penggantian</h2>@forelse($history as $entry)<article class="bk-task"><h3>{{ $entry->lama }} → {{ $entry->baru }}</h3><p>{{ $entry->dicatat_pada }} WIB · Petugas #{{ $entry->pengguna_id }}</p><p>{{ $entry->alasan }}</p></article>@empty<p>Belum ada penggantian untuk item ini.</p>@endforelse
{{ $history->links('partials.pagination') }}</section>
@endsection

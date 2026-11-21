@extends('layouts.admin')
@section('title','Daftar pesanan · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<header><p class="kc-kicker">Operasional · Pesanan</p><h1>Daftar pesanan</h1><p>Cari nomor pesanan atau nama pemesan. Filter tanggal menggunakan tanggal serah, bukan tanggal pembayaran.</p></header>
@if(config('checkout.staging') && app()->environment('local','testing','staging'))<a class="bk-secondary" href="{{ route('admin.permohonan-batal.index') }}">Tinjau permohonan pembatalan</a> <a class="bk-secondary" href="{{ route('admin.pesanan.manual-sewa') }}">Buat pesanan sewa manual</a> <a class="bk-secondary" href="{{ route('toko.form') }}">Catat penjualan tunai toko</a>@endif
<form class="bk-product-editor grid gap-3 p-4" method="get" action="{{ route('admin.pesanan.index') }}#hasil-pesanan" role="search" aria-label="Cari pesanan">
<label>Nomor atau nama pemesan <input type="search" name="q" maxlength="150" value="{{ $filters['q'] ?? '' }}"></label>
@isset($filters['q'])<a href="{{ route('admin.pesanan.index',array_diff_key($filters,['q'=>true])) }}#hasil-pesanan">Hapus pencarian</a>@endisset
<label>Status pesanan <select name="status"><option value="">Semua status</option>@foreach(\App\Enums\OrderStatus::cases() as $state)<option value="{{ $state->value }}" @selected(($filters['status'] ?? '')===$state->value)>{{ ucfirst(str_replace('_',' ',$state->value)) }}</option>@endforeach</select></label>
<label>Tanggal serah <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"></label>
@isset($filters['pelanggan'])<input type="hidden" name="pelanggan" value="{{ $filters['pelanggan'] }}"><p>Pelanggan #{{ $filters['pelanggan'] }}</p>@endisset
<button class="btn-primary">Tampilkan pesanan</button>
@if(array_diff_key($filters,['q'=>true]))<a href="{{ route('admin.pesanan.index',array_intersect_key($filters,['q'=>true])) }}#hasil-pesanan">Reset filter</a>@endif</form>
<div id="hasil-pesanan" tabindex="-1"><p>{{ $rows->total() }} pesanan ditemukan.</p>
@forelse($rows as $row)<article class="bk-task" id="pesanan-{{ $row->id }}"><header><h2>{{ $row->nomor }}</h2><span class="bk-order-status">{{ str_replace('_',' ',$row->status) }}</span></header>
<p>Tanggal serah: {{ $row->tanggal_serah?->format('d/m/Y') ?? 'Belum ditentukan' }}</p><p>Total Rp{{ number_format($row->total,0,',','.') }} · Saldo Rp{{ number_format($row->saldo,0,',','.') }}</p>
<a class="bk-secondary" href="{{ route('admin.pesanan.show',['id'=>$row->id]+$context) }}">Periksa {{ $row->nomor }}</a></article>@empty<p>Tidak ada pesanan pada halaman ini.</p>@endforelse
{{ $rows->links('partials.pagination') }}</div></section>
@endsection

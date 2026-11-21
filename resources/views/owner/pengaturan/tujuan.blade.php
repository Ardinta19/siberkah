@extends('layouts.admin')
@section('title','Tujuan pembayaran · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pengaturan.index')">Kembali ke pengaturan</x-back-link>
<header><p class="kc-kicker">Owner · Pengaturan</p><h1>Tujuan pembayaran</h1><p>Tambah tujuan pengganti jika detail rekening atau QRIS berubah. Tujuan baru tidak langsung ditawarkan kepada pelanggan.</p></header>
@if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
@foreach(['bank'=>'rekening bank','qris'=>'QRIS'] as $jenis=>$label)
<details class="bk-confirm" @if(old('jenis')===$jenis && $errors->any()) open @endif><summary>Tambah {{ $label }}</summary>
<form class="bk-product-editor grid gap-3 p-4" method="post" enctype="multipart/form-data" data-pending="Menyimpan tujuan…" action="{{ route('tujuan.store') }}">@csrf
<input type="hidden" name="asal" value="editor"><input type="hidden" name="jenis" value="{{ $jenis }}">
@if(old('jenis')===$jenis && $errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<label>Nama penerima {{ $label }} <input name="atas_nama" required maxlength="100" value="{{ old('jenis')===$jenis ? old('atas_nama') : '' }}"></label>
@if($jenis==='bank')<label>Bank <select required name="nama_bank">@foreach(['BCA','BRI','Mandiri','Bank Jambi'] as $bank)<option @selected(old('nama_bank')===$bank)>{{ $bank }}</option>@endforeach</select></label>
<label>Nomor rekening <input required name="nomor_rekening" inputmode="numeric" pattern="[0-9]{5,40}" minlength="5" maxlength="40" value="{{ old('nomor_rekening') }}"></label>
@else<label>Gambar QRIS statis (PNG) <input type="file" name="qris" accept="image/png" required></label><small>Maksimal 512 KB dan 1500 × 1500 piksel. Setelah gagal validasi, pilih kembali berkasnya.</small>@endif
<button class="btn-primary">Simpan {{ $label }} sebagai nonaktif</button></form></details>
@endforeach
<h2>Daftar tujuan ({{ $rows->total() }})</h2>
@forelse($rows as $row)<article class="bk-task" id="tujuan-{{ $row->id }}"><header><h3>{{ $row->nama_bank }} · {{ $row->atas_nama }}</h3><span class="bk-order-status">{{ $row->aktif ? 'Aktif' : 'Nonaktif' }}</span></header>
@if(session('tujuan_disimpan')==$row->id)<p role="status">Status tujuan tersimpan.</p>@endif
<p>Tujuan #{{ $row->id }}</p>
@if($row->jenis==='qris')<details><summary>Periksa gambar QRIS</summary><img width="256" loading="lazy" alt="QRIS {{ $row->atas_nama }}" src="data:image/png;base64,{{ $row->qris_png }}"></details>@else<p>Nomor rekening: {{ $row->nomor_rekening }}</p>@endif
<details class="bk-confirm"><summary>{{ $row->aktif ? 'Nonaktifkan' : 'Aktifkan' }} tujuan #{{ $row->id }}</summary><p>{{ $row->aktif ? 'Tujuan ini tidak lagi tersedia untuk dipilih. Pastikan ada pengganti bila masih menerima transfer.' : 'Cocokkan nama penerima dan nomor rekening atau periksa QRIS di aplikasi pembayaran sebelum mengaktifkan.' }}</p>
<form method="post" action="{{ route('tujuan.status',['id'=>$row->id,'page'=>$rows->currentPage()]) }}" data-pending="Menyimpan status…">@csrf @method('patch')<input type="hidden" name="asal" value="editor"><input type="hidden" name="aktif" value="{{ $row->aktif ? 0 : 1 }}"><button class="bk-secondary">Ya, {{ $row->aktif ? 'nonaktifkan' : 'aktifkan' }} tujuan</button></form></details></article>
@empty<p>Belum ada tujuan pembayaran.</p>@endforelse
{{ $rows->links('partials.pagination') }}</section>
@endsection

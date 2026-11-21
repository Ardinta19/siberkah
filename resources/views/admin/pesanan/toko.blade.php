@extends('layouts.admin')
@section('title','Penjualan tunai toko · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pesanan.index')">Kembali ke daftar pesanan</x-back-link>
<header><p class="kc-kicker">Kasir · Ambil di toko</p><h1>Penjualan tunai toko</h1><p>Untuk satu varian siap jual tanpa personalisasi. Bukan pesanan sewa, transfer bank, atau setoran kurir.</p></header>
@if($errors->any())<ul class="bk-notice" role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@if(!$customer)<form method="post" action="{{ route('admin.pesanan.manual-pelanggan') }}" class="bk-product-editor grid gap-3 p-4">@csrf<input type="hidden" name="tujuan" value="toko">
<label>Nomor WhatsApp pelanggan terverifikasi<input required type="tel" name="nomor_wa" maxlength="16" placeholder="08… atau +62…"></label><button class="btn-primary">Temukan pelanggan</button></form>
@else<h2>{{ $customer->nama }}</h2><p>Nomor berakhir {{ substr($customer->nomor_wa,-4) }} · <a href="{{ route('toko.form') }}">Ganti pelanggan</a></p>
@if($variants->isEmpty())<p>Tidak ada varian standar tersedia untuk penjualan hari ini.</p>
@else<form data-counter-sale class="bk-product-editor grid gap-3 p-4" method="post" data-pending="Mencatat penjualan…" action="{{ route('toko.store') }}">@csrf
<input type="hidden" name="kunci" value="{{ old('kunci',\Illuminate\Support\Str::uuid()) }}"><input type="hidden" name="pelanggan_id" value="{{ $customer->id }}"><input type="hidden" name="asal_form" value="toko"><input type="hidden" name="versi_privasi" value="{{ config('checkout.privacy_version') }}">
<label>Varian siap jual<select required name="varian_id"><option value="">Pilih varian</option>@foreach($variants as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga }}" data-stock="{{ $v->stok }}" @selected(old('varian_id')==$v->id)>{{ $v->produk }} · {{ $v->nama }} · Rp{{ number_format($v->harga,0,',','.') }} · stok {{ $v->stok }}</option>@endforeach</select></label>
<label>Jumlah barang<input type="number" required name="jumlah" min="1" max="100" value="{{ old('jumlah',1) }}"></label>
<p>Jumlah harga barang dibulatkan ke atas ke kelipatan Rp1.000.</p><p role="status" data-counter-total></p>
<label>Nominal pembayaran tunai setelah kembalian (Rp)<input required type="number" name="nominal" min="1" step="1"></label>
<label><input type="checkbox" name="setuju_privasi" required value="1"> Pelanggan menyetujui kebijakan privasi.</label><label><input type="checkbox" name="tunai_diperiksa" required value="1"> Saya sudah memeriksa uang tunai dan jumlah barang.</label>
<p>Menyimpan akan mengurangi stok dan mencatat pembayaran penuh. Barang belum ditandai diserahkan.</p><button class="btn-primary">Simpan penjualan tunai</button></form>@endif @endif</section>
@endsection

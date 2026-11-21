@extends('layouts.admin')
@section('title','Pesanan buket manual · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pesanan.index')">Kembali ke daftar pesanan</x-back-link><header><p class="kc-kicker">Layanan pelanggan · WhatsApp atau toko</p><h1>Pesanan buket manual</h1><p>Centang produk pada halaman ini untuk satu pesanan gabungan, atau buka satu produk langsung. Jumlah dan personalisasi diisi pada langkah berikutnya. Untuk barang standar yang sudah dibayar tunai di kasir, gunakan penjualan tunai toko.</p></header>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
@if(!$customer)<form method="post" action="{{ route('admin.pesanan.manual-pelanggan') }}" class="bk-product-editor grid gap-3 p-4">@csrf<input type="hidden" name="tujuan" value="buket"><label>Nomor WhatsApp pelanggan terverifikasi<input required type="tel" name="nomor_wa" maxlength="16" placeholder="08… atau +62…"></label><button class="btn-primary">Temukan pelanggan</button></form>
@else<h2>{{ $customer->nama }}</h2><p>Nomor berakhir {{ substr($customer->nomor_wa,-4) }} · <a href="{{ route('admin.buket.pilih') }}">Ganti pelanggan</a></p>
<form method="get" action="{{ route('admin.buket.pilih') }}"><input type="hidden" name="pelanggan" value="{{ $customer->id }}"><input type="hidden" name="susun" value="1">
@forelse($products as $product)<article class="bk-task"><h3><label><input type="checkbox" name="produk[]" value="{{ $product->id }}"> {{ $product->nama }}</label></h3><p>{{ $product->jenis==='custom'?'Custom':'Siap jual' }}</p><a class="bk-secondary" href="{{ route('admin.buket.form',[$customer->id,$product->slug]) }}">Pilih {{ $product->nama }}</a></article>@empty<p>Belum ada buket pada halaman ini.</p>@endforelse
@if($products->isNotEmpty())<button class="btn-primary">Susun produk terpilih</button>@endif</form>
{{ $products->links('partials.pagination') }}@endif</section>
@endsection

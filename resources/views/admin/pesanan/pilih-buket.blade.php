@extends('layouts.admin')
@section('title','Pesanan buket manual · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pesanan.index')">Kembali ke daftar pesanan</x-back-link><header><p class="kc-kicker">Layanan pelanggan · WhatsApp atau toko</p><h1>Pesanan buket manual</h1><p>Pilih satu produk siap jual atau custom, lalu tentukan jumlah dan personalisasinya. Untuk barang standar yang sudah dibayar tunai di kasir, gunakan penjualan tunai toko.</p></header>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
@if(!$customer)<form method="post" action="{{ route('admin.pesanan.manual-pelanggan') }}" class="bk-product-editor grid gap-3 p-4">@csrf<input type="hidden" name="tujuan" value="buket"><label>Nomor WhatsApp pelanggan terverifikasi<input required type="tel" name="nomor_wa" maxlength="16" placeholder="08… atau +62…"></label><button class="btn-primary">Temukan pelanggan</button></form>
@else<h2>{{ $customer->nama }}</h2><p>Nomor berakhir {{ substr($customer->nomor_wa,-4) }} · <a href="{{ route('admin.buket.pilih') }}">Ganti pelanggan</a></p>
@forelse($products as $product)<article class="bk-task"><h3>{{ $product->nama }}</h3><p>{{ $product->jenis==='custom'?'Custom':'Siap jual' }}</p><a class="bk-secondary" href="{{ route('admin.buket.form',[$customer->id,$product->slug]) }}">Pilih {{ $product->nama }}</a></article>@empty<p>Belum ada buket pada halaman ini.</p>@endforelse
{{ $products->links('partials.pagination') }}@endif</section>
@endsection

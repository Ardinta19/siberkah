@extends('layouts.admin')
@section('title','Pesanan sewa manual · SIBERKAH')
@section('content')
<section class="bk-owner-list"><x-back-link :href="route('admin.pesanan.index')">Kembali ke daftar pesanan</x-back-link>
<header><p class="kc-kicker">Layanan pelanggan · WhatsApp atau toko</p><h1>Pesanan sewa manual</h1><p>Catat pesanan antar untuk tanggal mendatang. Ini bukan penjualan langsung atau bukti pembayaran.</p></header>
@if($errors->any())<div class="bk-notice" role="alert"><strong>Belum tersimpan.</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(!$customer)<form method="post" action="{{ route('admin.pesanan.manual-pelanggan') }}" class="bk-product-editor grid gap-3 p-4">@csrf
<label>Nomor WhatsApp pelanggan terverifikasi<input type="tel" name="nomor_wa" placeholder="08… atau +62…" required maxlength="16"></label><button class="btn-primary">Temukan pelanggan</button></form>
@else<h2>{{ $customer->nama }}</h2><p>Nomor berakhir {{ substr($customer->nomor_wa,-4) }} · <a href="{{ route('admin.pesanan.manual-sewa') }}">Ganti pelanggan</a></p>
@if($variants->isEmpty() || $zones->isEmpty())<p>Belum tersedia varian sewa atau zona antar aktif. Pesanan tidak dapat dibuat.</p>
@else<form method="post" action="{{ route('admin.pesanan.manual-store') }}" class="bk-product-editor grid gap-3 p-4" data-pending="Memeriksa reservasi…">@csrf
<input type="hidden" name="asal_form" value="manual-sewa"><input type="hidden" name="pelanggan_id" value="{{ $customer->id }}"><input type="hidden" name="versi_privasi" value="{{ config('checkout.privacy_version') }}"><input type="hidden" name="kunci" value="{{ old('kunci',\Illuminate\Support\Str::uuid()) }}">
<label>Produk dan varian<select name="varian_id" required><option value="">Pilih varian sewa</option>@foreach($variants as $v)<option value="{{ $v->id }}" @selected(old('varian_id')==$v->id)>{{ $v->produk }} · {{ $v->nama }} · Rp{{ number_format($v->harga,0,',','.') }}/unit</option>@endforeach</select></label>
<label>Jumlah unit<input type="number" name="jumlah" min="1" max="100" required value="{{ old('jumlah',1) }}"></label>
<label>Tanggal serah (WIB)<input type="date" name="tanggal" min="{{ today('Asia/Jakarta')->addDay()->toDateString() }}" required value="{{ old('tanggal') }}"></label>
<label>Metode teks<select name="metode_teks" required>@foreach(['spidol'=>'Spidol','templat'=>'Templat','cutting'=>'Cutting'] as $key=>$label)<option value="{{ $key }}" @selected(old('metode_teks')===$key)>{{ $label }}</option>@endforeach</select></label>
<label>Zona antar<select name="zona_antar_id" required><option value="">Pilih zona</option>@foreach($zones as $z)<option value="{{ $z->id }}" @selected(old('zona_antar_id')==$z->id)>{{ $z->nama }} · Rp{{ number_format($z->ongkir,0,',','.') }}</option>@endforeach</select></label>
<label>Alamat antar<textarea name="alamat_antar" required maxlength="500">{{ old('alamat_antar') }}</textarea></label>
<label>Pembayaran bank<select name="pembayaran" required><option value="penuh">Penuh</option><option value="dp" @selected(old('pembayaran')==='dp')>DP bila memenuhi syarat</option></select></label>
<label><input required type="checkbox" name="setuju_privasi" value="1"> Pelanggan menyetujui kebijakan privasi yang berlaku.</label>
<label><input required type="checkbox" name="persetujuan_pelanggan_diperiksa" value="1"> Saya telah memeriksa persetujuan dan rincian pesanan dengan pelanggan.</label>
<p>Ketersediaan unit, jadwal, ongkir, dan syarat DP diperiksa saat disimpan. Tidak ada pengecualian stok atau tarif manual.</p>
<button class="btn-primary">Buat pesanan sewa</button></form>@endif @endif</section>
@endsection

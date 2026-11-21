@extends($manualCustomer ? 'layouts.admin' : 'layouts.customer')
@section('title', 'Pesan '.$product->nama.' · SIBERKAH')
@section('content')
<section class="bk-checkout-page">
    @if($manualCustomer)<x-back-link :href="route('admin.buket.pilih',['pelanggan'=>$manualCustomer->id])">Kembali ke pilihan buket</x-back-link>
    @elseif(request()->boolean('dari_keranjang'))
        <x-back-link href="{{ route('cart.index') }}#keranjang-{{ $product->id }}">Kembali ke keranjang</x-back-link>
    @else
        @php($koleksi = collect((array)request('koleksi', []))->only(['q','jenis','kategori','tanggal','urut','page'])->filter(fn ($v) => is_scalar($v))->all())
        <x-back-link :href="route('catalog.show', ['slug'=>$product->slug,'tanggal'=>$day,'koleksi'=>$koleksi])">Kembali ke detail produk</x-back-link>
    @endif
    <p class="kc-kicker">{{ $manualCustomer ? 'Admin · Pesanan WhatsApp / toko' : 'Berkah Kreatif · Checkout' }}</p><h1>Pesan {{ $product->nama }}</h1>
    @if($manualCustomer)<p>Pemesan: {{ $manualCustomer->nama }} · Nomor berakhir {{ substr($manualCustomer->nomor_wa,-4) }}. Membuat pesanan tidak mencatat penerimaan uang.</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($options->contains(fn ($option) => $option->wajib && $option->tipe !== 'teks' && $option->nilaiOpsi->isEmpty()))
        <p role="status">Pilihan wajib produk ini belum tersedia. Hubungi Admin sebelum memesan.</p>
    @elseif(($needsVariant && $variants->isEmpty()) || ($product->jenis === 'sewa' && ($options->isNotEmpty() || $zones->isEmpty())))
        <p>Produk belum dapat dipesan melalui formulir ini. Hubungi Admin.</p>
    @else
    <nav class="bk-checkout-jumps" aria-label="Bagian checkout"><a href="#pilihan-produk">Pilihan produk</a><a href="#serah-produk">Serah terima</a><a href="#bayar-produk">Pembayaran</a></nav>
    <form data-pending="Membuat pesanan…" method="POST" action="{{ $manualCustomer ? route('admin.buket.submit',[$manualCustomer->id,$product->slug]) : route('checkout.submit', $product->slug) }}" id="checkout" class="grid gap-4" data-rendered-at="{{ now()->timestamp }}" data-price="{{ $product->harga_dasar }}" data-threshold="{{ $threshold }}" data-today="{{ today()->toDateString() }}">
        @csrf
        @if(!$manualCustomer && request()->boolean('dari_keranjang'))<input type="hidden" name="dari_keranjang" value="1">@endif
        <input type="hidden" name="kunci" value="{{ old('kunci', $key) }}">
        <input type="hidden" name="versi_privasi" value="{{ config('checkout.privacy_version') }}">
        <div class="bk-checkout-fields">
        <section class="bk-checkout-card" aria-labelledby="pilihan-produk"><h2 id="pilihan-produk" tabindex="-1"><span class="bk-section-petal" aria-hidden="true"></span>Pilihan produk</h2>
        @if($needsVariant)<label>Varian <select name="varian_id" required>@foreach($variants as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga }}" @selected(old('varian_id') == $v->id)>{{ $v->nama }} · Rp{{ number_format($v->harga,0,',','.') }}</option>@endforeach</select></label>@endif
        @include('partials.quantity',['itemId'=>'checkout-'.$product->id,'quantity'=>old('jumlah',!$manualCustomer && request()->boolean('dari_keranjang') ? session('cart.'.$product->id,1) : 1)])
        @foreach($options as $o)
            <label>{{ $o->nama }} @if($o->wajib)(wajib)@endif
            @if($o->tipe === 'teks')<input maxlength="300" name="opsi[{{ $o->id }}][teks]" value="{{ old('opsi.'.$o->id.'.teks') }}" @required($o->wajib)>
            @else
                <select data-extra="{{ $o->id }}" name="opsi[{{ $o->id }}][nilai_id]" @required($o->wajib)><option value="">{{ $o->wajib ? 'Pilih opsi' : 'Tanpa tambahan' }}</option>@foreach($o->nilaiOpsi as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga_tambahan }}" @selected(old('opsi.'.$o->id.'.nilai_id') == $v->id)>{{ $v->label }} (+Rp{{ number_format($v->harga_tambahan,0,',','.') }})</option>@endforeach</select>
            @endif</label>
            @if($o->tipe === 'satuan')<label>Jumlah {{ $o->nama }} per produk <input type="number" name="opsi[{{ $o->id }}][satuan]" min="1" max="100" value="{{ old('opsi.'.$o->id.'.satuan',1) }}"></label>@endif
        @endforeach
        </section><section class="bk-checkout-card" aria-labelledby="serah-produk"><h2 id="serah-produk" tabindex="-1"><span class="bk-section-petal" aria-hidden="true"></span>Serah terima</h2>
        <label>Tanggal serah <input type="date" required name="tanggal" min="{{ today()->toDateString() }}" value="{{ old('tanggal',$day) }}"></label>
        @if($product->jenis === 'sewa')
            <label>Metode teks <select name="metode_teks">@foreach(['spidol','templat','cutting'] as $m)<option @selected(old('metode_teks') === $m)>{{ $m }}</option>@endforeach</select></label>
            <label id="slot">Jam serah (khusus hari ini) <input type="time" name="jam_serah" min="07:00" max="17:00" value="{{ old('jam_serah') }}"></label>
        @endif
        <label>Cara serah <select name="cara_serah">@if($product->jenis !== 'sewa')<option value="ambil">Ambil di toko</option>@endif @if($zones->isNotEmpty())<option value="antar" @selected(old('cara_serah') === 'antar')>Diantar</option>@endif</select></label>
        <fieldset id="delivery" class="grid gap-2"><legend>Alamat pengantaran</legend>
            <label>Zona <select name="zona_antar_id">@foreach($zones as $z)<option value="{{ $z->id }}" data-price="{{ $z->ongkir }}" @selected(old('zona_antar_id') == $z->id)>{{ $z->nama }} · Rp{{ number_format($z->ongkir,0,',','.') }}</option>@endforeach</select></label>
            <label>Alamat <textarea maxlength="500" name="alamat_antar">{{ old('alamat_antar') }}</textarea></label>
        </fieldset>
        </section><section class="bk-checkout-card" aria-labelledby="bayar-produk"><h2 id="bayar-produk" tabindex="-1"><span class="bk-section-petal" aria-hidden="true"></span>Pembayaran</h2>
        <label>Skema pembayaran <select name="pembayaran"><option value="penuh">Penuh</option>@if($bank)<option value="dp" @selected(old('pembayaran') === 'dp')>DP 50% di muka</option>@endif</select></label>
        <p>DP tersedia mulai Rp{{ number_format($threshold,0,',','.') }} bila tenggat H-1 pukul 18.00 WIB belum lewat.</p>
        <label>Metode pelunasan <select name="metode_pelunasan">@if($bank)<option value="bank">Transfer / QRIS sesuai tujuan aktif</option>@endif<option value="tunai_saat_serah" @selected(old('metode_pelunasan') === 'tunai_saat_serah')>Tunai saat ambil atau diantar</option></select></label>
        </section></div>
        <div class="bk-checkout-review">
        <section hidden id="estimate" class="bk-estimate" aria-labelledby="estimate-title">
            <p class="kc-kicker">Sebelum memesan</p><h2 id="estimate-title">Ringkasan biaya</h2>
            <dl><div hidden data-base-row><dt>Harga sebelum tambahan (per produk)</dt><dd data-base></dd></div>
                <div hidden data-extra-row><dt>Tambahan pilihan (per produk)</dt><dd data-extra-cost></dd></div>
                <div><dt>Subtotal produk</dt><dd data-subtotal></dd></div><div><dt>Ongkos antar</dt><dd data-fee></dd></div>
                <div hidden data-rounding-row><dt>Pembulatan</dt><dd data-rounding></dd></div>
                <div class="bk-estimate-total"><dt>Perkiraan total</dt><dd data-total></dd></div></dl>
            <p data-payment-note role="status"></p>
        </section>
        <p>Total akhir dan ketersediaan diperiksa server saat memesan. Jangan membayar sebelum melihat tagihan.</p>
        <label><input type="checkbox" required name="setuju_privasi" value="1"> {{ $manualCustomer ? 'Pelanggan menyetujui' : 'Saya menyetujui' }} <a href="{{ route('privasi') }}" target="_blank" rel="noopener">kebijakan privasi</a> versi {{ config('checkout.privacy_version') }}.</label>
        @if($manualCustomer)<label><input type="checkbox" name="persetujuan_pelanggan_diperiksa" required value="1"> Saya sudah memeriksa pilihan, jadwal dan persetujuan pelanggan.</label>@endif
        <button type="submit" class="btn-primary">Buat pesanan</button>
        </div>
    </form>

    @endif
</section>
@endsection

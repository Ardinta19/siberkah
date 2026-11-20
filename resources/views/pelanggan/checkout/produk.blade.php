@extends('layouts.customer')
@section('content')
<section class="mx-auto max-w-xl space-y-4 p-6">
    <a href="{{ route('catalog.show', $product->slug) }}">Kembali ke detail produk</a>
    <h1 class="text-2xl font-bold">Pesan {{ $product->nama }}</h1>
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if(($needsVariant && $variants->isEmpty()) || ($product->jenis === 'sewa' && ($options->isNotEmpty() || $zones->isEmpty())))
        <p>Produk belum dapat dipesan melalui formulir ini. Hubungi Admin.</p>
    @else
    <form method="POST" action="{{ route('checkout.submit', $product->slug) }}" id="checkout" class="grid gap-4" data-price="{{ $product->harga_dasar }}" data-threshold="{{ $threshold }}" data-today="{{ today()->toDateString() }}">
        @csrf
        @if(request()->boolean('dari_keranjang'))<input type="hidden" name="dari_keranjang" value="1">@endif
        <input type="hidden" name="kunci" value="{{ old('kunci', $key) }}">
        <input type="hidden" name="versi_privasi" value="{{ config('checkout.privacy_version') }}">
        @if($needsVariant)<label>Varian <select name="varian_id" required>@foreach($variants as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga }}" @selected(old('varian_id') == $v->id)>{{ $v->nama }} · Rp{{ number_format($v->harga,0,',','.') }}</option>@endforeach</select></label>@endif
        <label>Jumlah <input required name="jumlah" type="number" min="1" max="100" value="{{ old('jumlah',request()->boolean('dari_keranjang') ? session('cart.'.$product->id,1) : 1) }}"></label>
        @foreach($options as $o)
            <label>{{ $o->nama }} @if($o->wajib)(wajib)@endif
            @if($o->tipe === 'teks')<input maxlength="300" name="opsi[{{ $o->id }}][teks]" value="{{ old('opsi.'.$o->id.'.teks') }}" @required($o->wajib)>
            @else
                <select data-extra="{{ $o->id }}" name="opsi[{{ $o->id }}][nilai_id]" @required($o->wajib)><option value="">Pilih opsi</option>@foreach($o->nilaiOpsi as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga_tambahan }}" @selected(old('opsi.'.$o->id.'.nilai_id') == $v->id)>{{ $v->label }} (+Rp{{ number_format($v->harga_tambahan,0,',','.') }})</option>@endforeach</select>
            @endif</label>
            @if($o->tipe === 'satuan')<label>Satuan {{ $o->nama }} <input type="number" name="opsi[{{ $o->id }}][satuan]" min="1" max="100" required value="{{ old('opsi.'.$o->id.'.satuan',1) }}"></label>@endif
        @endforeach
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
        <label>Skema pembayaran <select name="pembayaran"><option value="penuh">Penuh</option>@if($bank)<option value="dp" @selected(old('pembayaran') === 'dp')>DP 50% di muka</option>@endif</select></label>
        <p>DP tersedia mulai Rp{{ number_format($threshold,0,',','.') }} bila tenggat H-1 pukul 18.00 WIB belum lewat.</p>
        <label>Metode pelunasan <select name="metode_pelunasan">@if($bank)<option value="bank">Transfer / QRIS sesuai tujuan aktif</option>@endif<option value="tunai_saat_serah" @selected(old('metode_pelunasan') === 'tunai_saat_serah')>Tunai saat ambil atau diantar</option></select></label>
        <output id="estimate" aria-live="polite"></output>
        <p>Total akhir dan ketersediaan diperiksa server saat memesan. Jangan membayar sebelum melihat tagihan.</p>
        <label><input type="checkbox" required name="setuju_privasi" value="1"> Saya menyetujui <a href="{{ route('privasi') }}" target="_blank" rel="noopener">kebijakan privasi</a> versi {{ config('checkout.privacy_version') }}.</label>
        <button class="btn-primary">Buat pesanan</button>
    </form>
    <script>
    const form = document.querySelector('#checkout'), field = name => form.elements.namedItem(name);
    const price = select => Number(select?.selectedOptions[0]?.dataset.price || 0);
    function updateEstimate() {
        const delivery = field('cara_serah').value === 'antar';
        document.querySelector('#delivery').disabled = !delivery;
        document.querySelector('#delivery').hidden = !delivery;
        field('alamat_antar').required = delivery;
        if (field('jam_serah')) {
            const sameDay = field('tanggal').value === form.dataset.today;
            field('jam_serah').disabled = !sameDay;
            field('jam_serah').required = sameDay;
            document.querySelector('#slot').hidden = !sameDay;
        }
        let unit = field('varian_id') ? price(field('varian_id')) : Number(form.dataset.price);
        for (const opt of form.querySelectorAll('[data-extra]')) unit += price(opt) * Number(field(`opsi[${opt.dataset.extra}][satuan]`)?.value ?? 1);
        const total = Math.ceil((unit * Number(field('jumlah').value) + (delivery ? price(field('zona_antar_id')) : 0)) / 1000) * 1000;
        const dp = field('pembayaran').querySelector('[value="dp"]');
        if (dp) { dp.disabled = total < Number(form.dataset.threshold); if (dp.disabled) field('pembayaran').value = 'penuh'; }
        const amount = new Intl.NumberFormat('id-ID').format(total);
        document.querySelector('#estimate').textContent = `Perkiraan total Rp${amount}` + (field('pembayaran').value === 'dp' ? ` · DP Rp${new Intl.NumberFormat('id-ID').format(total / 2)}; sisanya saat pelunasan.` : '');
    }
    form.addEventListener('input', updateEstimate); updateEstimate();
    </script>
    @endif
</section>
@endsection

@extends('layouts.customer')
@section('title', 'Tagihan '.$nomor)
@section('content')
<section class="container-siberkah py-8">
    <x-back-link :href="route('pesanan.show',['nomor'=>$nomor]+array_filter(['kelompok'=>in_array(request('kelompok'),['aktif','arsip'],true)?request('kelompok'):null,'page'=>request()->integer('page') > 1 ? request()->integer('page') : null]))">Kembali ke rincian pesanan</x-back-link>
    <h1 class="text-2xl font-bold">Tagihan {{ $nomor }}</h1>
    <p>Status pesanan: {{ str_replace('_', ' ', $status) }} · Saldo Rp{{ number_format($saldo, 0, ',', '.') }}</p>
    @if($tunai_saat_serah)<p>Metode pelunasan: tunai saat {{ $cara_serah === 'ambil' ? 'ambil di toko' : 'diantar' }}. Jangan transfer bagian yang dibayar tunai.</p>@endif
    @if($errors->any())<div class="bk-notice" role="alert"><strong>Konfirmasi belum terkirim.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @foreach($pembayaran as $bill)
        <article class="bk-bill my-4 border p-4">
            <h2>Tagihan {{ $bill->jenis }} · {{ str_replace('_',' ', $bill->status) }}</h2>
            @if($bill->boleh_bayar)
                <div data-payment-window data-end="{{ $bill->batas_wib }}" data-server="{{ $waktu_server }}">
                    <dl class="bk-bill-amount"><div><dt>Pokok tagihan</dt><dd data-principal>Rp{{ number_format($bill->tagihan,0,',','.') }}</dd></div>
                        <div><dt>Kode unik</dt><dd data-code>+ Rp{{ number_format($bill->kode_unik,0,',','.') }}</dd></div>
                        <div><dt>Bayar persis</dt><dd data-exact>Rp{{ number_format($bill->nominal_diharapkan,0,',','.') }}</dd></div></dl>
                    <button hidden type="button" data-copy-amount="{{ $bill->nominal_diharapkan }}" class="bk-secondary">Salin nominal transfer</button>
                    <span data-copy-status role="status"></span>
                    <p>Nominal sudah termasuk kode unik. Pilih satu tujuan saja.</p>
                    @if($bill->batas_wib)<p>Batas: {{ \Illuminate\Support\Carbon::parse($bill->batas_wib)->format('d/m/Y H:i:s') }} WIB. <span data-clock></span></p>@endif
                    <form data-pending="Mengirim konfirmasi…" method="post" enctype="multipart/form-data" action="{{ route('payment.confirm', $nomor) }}">@csrf
                        <input type="hidden" name="pembayaran_id" value="{{ $bill->id }}">
                        @foreach($tujuan as $account)
                            <label class="block my-3"><input type="radio" name="tujuan_id" value="{{ $account->id }}" @checked((string)old('pembayaran_id') === (string)$bill->id && (string)old('tujuan_id') === (string)$account->id) required>
                                {{ $account->nama_bank }} {{ $account->nomor_rekening }} · {{ $account->atas_nama }}
                                @if($account->jenis === 'qris')<img width="256" alt="QRIS statis {{ $account->atas_nama }}" src="data:image/png;base64,{{ $account->qris_png }}">@endif
                            </label>
                        @endforeach
                        <label>Bukti transfer (opsional, JPG/PNG/WebP, maksimal 2 MB dan 2048 × 2048 piksel) <input type="file" name="bukti" accept="image/jpeg,image/png,image/webp"></label>
                        <p>Potong bagian yang tidak terkait pembayaran. Jangan unggah identitas, PIN atau kode OTP.</p>
                        <p>Periksa nama penerima di aplikasi pembayaran. Jika berbeda, jangan bayar.</p>
                        <button class="btn-primary">Saya sudah membayar ke tujuan yang dipilih</button>
                    </form>
                </div>
                <p hidden tabindex="-1" role="alert" data-expired>Waktu pembayaran habis. Jangan bayar; jika sudah mengirim dana, hubungi Admin.</p>
            @elseif($bill->dikonfirmasi_pelanggan_pada && $bill->status === 'menunggu')
                <p>Konfirmasi diterima, menunggu pemeriksaan Admin. Jangan bayar lagi.</p>
            @elseif($bill->status === 'menunggu')
                <p>Tidak ada instruksi pembayaran aktif. Jangan bayar; hubungi Admin bila dana sudah dikirim.</p>
            @endif
        </article>
    @endforeach

</section>
@endsection

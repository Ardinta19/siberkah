@extends('layouts.customer')
@section('title', 'Tagihan '.$nomor)
@section('content')
<section class="container-siberkah py-8">
    <h1 class="text-2xl font-bold">Tagihan {{ $nomor }}</h1>
    <p>Status pesanan: {{ str_replace('_', ' ', $status) }} · Saldo Rp{{ number_format($saldo, 0, ',', '.') }}</p>
    @if($tunai_saat_serah)<p>Metode pelunasan: tunai saat {{ $cara_serah === 'ambil' ? 'ambil di toko' : 'diantar' }}. Jangan transfer bagian yang dibayar tunai.</p>@endif
    @foreach($pembayaran as $bill)
        <article class="my-4 border p-4">
            <h2>Tagihan {{ $bill->jenis }} · {{ str_replace('_',' ', $bill->status) }}</h2>
            @if($bill->boleh_bayar)
                <div data-payment-window data-end="{{ $bill->batas_wib }}" data-server="{{ $waktu_server }}">
                    <p>Bayar persis <strong>Rp{{ number_format($bill->nominal_diharapkan, 0, ',', '.') }}</strong>, termasuk kode unik. Pilih satu tujuan saja.</p>
                    @if($bill->batas_wib)<p>Batas: {{ \Illuminate\Support\Carbon::parse($bill->batas_wib)->format('d/m/Y H:i:s') }} WIB. <span data-clock></span></p>@endif
                    <form method="post" enctype="multipart/form-data" action="{{ route('payment.confirm', $nomor) }}">@csrf
                        <input type="hidden" name="pembayaran_id" value="{{ $bill->id }}">
                        @foreach($tujuan as $account)
                            <label class="block my-3"><input type="radio" name="tujuan_id" value="{{ $account->id }}" required>
                                {{ $account->nama_bank }} {{ $account->nomor_rekening }} · {{ $account->atas_nama }}
                                @if($account->jenis === 'qris')<img width="256" alt="QRIS statis {{ $account->atas_nama }}" src="data:image/png;base64,{{ $account->qris_png }}">@endif
                            </label>
                        @endforeach
                        <label>Bukti transfer (opsional, JPG/PNG/WebP, maksimal 2 MB) <input type="file" name="bukti" accept="image/jpeg,image/png,image/webp"></label>
                        <p>Potong bagian yang tidak terkait pembayaran. Jangan unggah identitas, PIN atau kode OTP.</p>
                        <p>Periksa nama penerima di aplikasi pembayaran. Jika berbeda, jangan bayar.</p>
                        <button class="btn-primary">Saya sudah membayar ke tujuan yang dipilih</button>
                    </form>
                </div>
                <p hidden data-expired>Waktu pembayaran habis. Jangan bayar; jika sudah mengirim dana, hubungi Admin.</p>
            @elseif($bill->dikonfirmasi_pelanggan_pada && $bill->status === 'menunggu')
                <p>Konfirmasi diterima, menunggu pemeriksaan Admin. Jangan bayar lagi.</p>
            @elseif($bill->status === 'menunggu')
                <p>Tidak ada instruksi pembayaran aktif. Jangan bayar; hubungi Admin bila dana sudah dikirim.</p>
            @endif
        </article>
    @endforeach
    <a href="{{ route('pesanan.show', $nomor) }}">Kembali ke rincian pesanan</a>
</section>
<script>
for (const panel of document.querySelectorAll('[data-payment-window][data-end]:not([data-end=""])')) {
    const remaining = Date.parse(panel.dataset.end) - Date.parse(panel.dataset.server);
    const start = performance.now();
    function tick() {
        const seconds = Math.ceil((remaining - performance.now() + start) / 1000);
        if (seconds <= 0) {
            panel.hidden = true;
            panel.nextElementSibling.hidden = false;
            return;
        }
        panel.querySelector('[data-clock]').textContent = `Sisa ${Math.floor(seconds / 60)} menit ${seconds % 60} detik.`;
        setTimeout(tick, 500);
    }
    tick();
}
</script>
@endsection

<section class="bk-task" aria-labelledby="aksi-tahap"><h2 id="aksi-tahap">Tindakan sesuai tahap</h2>
@if($action==='periksa')<p>Konfirmasi pembayaran atau mutasi masih perlu diperiksa. Pembatalan sebelum bayar tidak tersedia.</p>
@elseif($action==='permohonan')<a class="bk-secondary" href="{{ route('admin.permohonan-batal.index') }}">Tinjau permohonan pelanggan sebelum memutuskan</a>
@else
@php([$label,$route,$note] = match($action) {
    'T04'=>['Batalkan sebelum pembayaran','admin.pesanan.cancel-unpaid','Reservasi atau stok tertahan akan dilepas dan tagihan menunggu akan kedaluwarsa. Tidak ada refund.'],
    'T12'=>['Batalkan setelah konfirmasi','admin.pesanan.cancel-paid','Pesanan akan dibatalkan. Sistem menetapkan kewajiban refund menurut tahap, tanggal dan pokok terbayar. Tidak ada transfer otomatis.'],
    'T10'=>['Selesaikan pesanan nonsewa','toko.selesai','Barang sudah diserahkan dan saldo nol. Penutupan tidak mencatat pembayaran baru.'],
})
<details class="bk-confirm"><summary>{{ $label }}</summary><form data-ledger method="post" action="{{ route($route,$order->id) }}" class="bk-product-editor grid gap-3 p-4">@csrf
<p>{{ $note }}</p>@if($action!=='T10')<label>Alasan pembatalan<textarea required maxlength="300" name="alasan"></textarea></label>@endif
<label><input type="checkbox" required name="konfirmasi" value="1"> Saya sudah memeriksa pesanan {{ $order->nomor }} dan dampak tindakan ini.</label>
<button class="btn-primary">{{ $label }} · {{ $order->nomor }}</button>@include('partials.ledger-feedback')</form></details>
@endif</section>

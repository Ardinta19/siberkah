<section class="bk-task" aria-labelledby="refund-title" id="refund"><h2 id="refund-title">Refund pembatalan</h2>
<p>Keputusan {{ $refund->diputuskan_pada }} WIB · Kewajiban akhir <strong>Rp{{ number_format($refund->nominal_final,0,',','.') }}</strong></p>
@if($refund->alasan_override)<p>Perhitungan awal Rp{{ number_format($refund->nominal_sistem,0,',','.') }} · Alasan penyesuaian: {{ $refund->alasan_override }}</p>@endif
@if((int)$refund->nominal_final===0)<p>Tidak ada dana yang harus dikembalikan berdasarkan keputusan ini.</p>
@elseif($refund->status==='terverifikasi')<p>Refund sudah tercatat. Referensi keluar: <strong>{{ $refund->referensi_keluar }}</strong> · {{ $refund->diverifikasi_pada }} WIB.</p>
@elseif($refund->status==='menunggu')<p>Menunggu pencatatan mutasi keluar. Persetujuan pembatalan bukan bukti uang sudah dikirim.</p>
<details class="bk-confirm"><summary>Catat refund yang sudah ditransfer</summary>
<form method="post" class="bk-product-editor grid gap-3 p-4" action="{{ route('admin.pesanan.record-refund',$order->id) }}" data-ledger>@csrf
<p>Cocokkan mutasi bank untuk {{ $order->nomor }}. Formulir ini hanya mencatat transfer yang sudah terjadi.</p>
<label>Referensi keluar aktual <input required name="referensi_keluar" maxlength="100" autocomplete="off"></label>
<label>Nominal keluar aktual (Rp) <input required name="nominal" type="number" step="1" min="1"></label>
<label><input required type="checkbox"> Referensi dan nominal mutasi keluar sudah saya periksa.</label>
<button type="submit" class="btn-primary">Simpan pencatatan refund</button>@include('partials.ledger-feedback')
</form></details>
@else<p>Status tagihan refund perlu diperiksa. Pencatatan tidak tersedia.</p>@endif
</section>

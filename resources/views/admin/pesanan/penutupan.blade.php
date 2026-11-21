@php
    $rupiah = fn($n) => 'Rp'.number_format($n,0,',','.');
    $tunai = $order->jenis_jaminan === 'deposit';
    $sisa = (int)$order->nominal_jaminan - (int)($deposit->nominal ?? 0);
    $selesai = $order->status === 'selesai';
@endphp
<section class="bk-task" aria-labelledby="penutupan"><h2 id="penutupan">Penilaian dan penutupan sewa</h2>
<p>Saldo pesanan: {{ $rupiah($order->saldo) }}</p>
@if($lateFee)<p>Denda keterlambatan tercatat: {{ $rupiah($lateFee) }}. Denda terpisah dari biaya kerusakan dan bukan potongan deposit otomatis.</p>@endif
@if(!$review && $tunai)<p>Deposit saat serah: {{ $rupiah($order->nominal_jaminan) }}. Setoran deposit belum dikonfirmasi. Hitung kas pembayaran antar tidak mengonfirmasi penerimaan jaminan.</p>@endif
@if(!$review)<p>Belum ada penilaian final. Bukti inspeksi saja tidak cukup untuk menutup pesanan.</p>
@include('admin.pesanan.nilai-retur')
@else
<p>Dinilai {{ $review->dinilai_pada }} WIB · Petugas #{{ $review->penilai_id }}</p>
<p>Biaya kerusakan: <strong>{{ $rupiah($review->biaya_kerusakan) }}</strong> · {{ $review->alasan }}</p>
@foreach(json_decode($review->rincian_tarif ?? '[]',true) ?? [] as $line)<p>{{ ucfirst(str_replace('_',' ',$line['kode'])) }}: {{ $line['jumlah'] }} {{ $line['satuan'] }} × {{ $rupiah($line['tarif']) }}</p>@endforeach
@if($tunai)<h3>Deposit tunai</h3><dl><div><dt>Deposit awal</dt><dd>{{ $rupiah($order->nominal_jaminan) }}</dd></div><div><dt>Dipakai untuk kerusakan</dt><dd>{{ $rupiah($deposit->nominal ?? 0) }}</dd></div><div><dt>{{ $selesai ? 'Nominal kembali tercatat' : 'Sisa untuk dikembalikan' }}</dt><dd>{{ $rupiah($sisa) }}</dd></div></dl>
@if($deposit)<p>Alasan penggunaan: {{ $deposit->alasan }} · {{ $deposit->dicatat_pada }} WIB · Petugas #{{ $deposit->pengguna_id }}</p>@endif
@if($review->setoran_pada)<p>Setoran deposit tercatat {{ $review->setoran_pada }} WIB · Petugas #{{ $review->penerima_setoran_id }}</p>
@elseif(!$selesai && $order->status==='sudah_dikembalikan')<details class="bk-confirm"><summary>Catat penerimaan setoran deposit</summary>
<form data-ledger class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('retur.setoran',$order->id) }}">@csrf
<p>Hitung uang fisik. Pencatatan ini tidak memotong deposit dan tidak melunasi saldo.</p>
<label>Nominal deposit yang diterima (Rp)<input name="nominal" type="number" min="{{ $order->nominal_jaminan }}" max="{{ $order->nominal_jaminan }}" step="1" required></label>
<button class="btn-primary">Catat deposit yang sudah diterima</button>@include('partials.ledger-feedback')</form></details>@endif
@include('admin.pesanan.pemakaian-deposit')
@else<p>Jenis jaminan: {{ $order->jenis_jaminan ?? 'Tidak tercatat' }}</p>@endif
@if($selesai)<h3>Penutupan tercatat</h3><p>{{ $review->catatan_penutupan }}</p>
@elseif($order->status === 'sudah_dikembalikan' && $order->saldo === 0 && (!$tunai || $review->setoran_pada))
<details class="bk-confirm"><summary>Selesaikan pengembalian sewa</summary>
<form data-ledger class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('retur.selesai',$order->id) }}">@csrf
<p>Periksa jaminan sebelum menutup pesanan {{ $order->nomor }}. Tindakan ini tidak mentransfer uang.</p>
<label>Nominal aktual yang dikembalikan (Rp)<input name="nominal_kembali" type="number" min="0" required step="1"></label>
<label>Hasil pemeriksaan jaminan<select name="jaminan_dikembalikan" required><option value="">Pilih hasil aktual</option><option value="1">Jaminan atau sisa deposit telah dikembalikan</option><option value="0">Deposit habis diperhitungkan; tidak ada sisa</option></select></label>
<label>Catatan penutupan<textarea required name="catatan" maxlength="300"></textarea></label><button class="btn-primary">Catat penutupan pesanan</button>@include('partials.ledger-feedback')</form></details>
@else<p>Penutupan belum tersedia. Pastikan unit sudah kembali, saldo nol, dan setoran deposit tunai sudah tercatat.</p>@endif
@endif</section>

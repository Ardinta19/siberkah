<section aria-labelledby="jaminan-sewa"><h2 id="jaminan-sewa">Jaminan sewa Anda</h2>
@if($pesanan->jenis_jaminan === 'deposit')
    @php($rupiah = fn($n) => 'Rp'.number_format($n,0,',','.'))
    @php($sisa = $pesanan->nominal_jaminan - $depositDipakai)
    <p>Deposit saat serah: {{ $rupiah($pesanan->nominal_jaminan) }}. Deposit ini terpisah dari pembayaran sewa.</p>
    <p>Dipakai untuk biaya kerusakan: {{ $rupiah($depositDipakai) }}.</p>
    @if($pesanan->jaminan_kembali_pada)<p>Pengembalian deposit tercatat: {{ $rupiah($sisa) }} pada {{ $pesanan->jaminan_kembali_pada->format('d/m/Y H:i') }} WIB.</p>
    @elseif($pesanan->status === 'selesai' && $depositDipakai > 0 && $sisa === 0)<p>Seluruh deposit diperhitungkan untuk kerusakan. Tidak ada sisa untuk dikembalikan.</p>
    @else<p>Sisa deposit: {{ $rupiah($sisa) }}. Pengembalian belum tercatat.</p>@endif
@else<p>Jaminan dokumen: {{ strtoupper($pesanan->jenis_jaminan) }}.
    @if($pesanan->jaminan_kembali_pada)Pengembalian dokumen tercatat pada {{ $pesanan->jaminan_kembali_pada->format('d/m/Y H:i') }} WIB.
    @else Pengembalian dokumen belum tercatat.@endif</p>
@endif
</section>

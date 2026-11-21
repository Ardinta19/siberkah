@php
    $rupiah = static fn ($angka) => 'Rp'.number_format($angka,0,',','.');
    $selisih = $pesanan->total - $pesanan->subtotal - $pesanan->ongkir - $pesanan->penyesuaian;
@endphp
<section class="bk-estimate my-4" aria-labelledby="order-cost-title">
    <h2 id="order-cost-title">Rincian biaya pesanan</h2>
    <ul class="grid gap-3" aria-label="Produk yang dipesan">
        @foreach($pesanan->items as $item)
            <li class="border-b pb-3 break-words"><h3><strong>{{ $item->jumlah }} × {{ $item->nama_produk }}</strong></h3>
                @if($item->nama_varian)<p>Varian: {{ $item->nama_varian }}</p>@endif
                @foreach($opsiPerItem->get($item->id,collect()) as $opsi)
                    <p>{{ $opsi->nama_opsi }}: {{ $opsi->label_nilai }}@if($opsi->teks_bebas !== null) · {{ $opsi->teks_bebas }}@if($opsi->nilai_opsi_id !== null) satuan per produk@endif@endif
                    @if($opsi->harga_tambahan) (+{{ $rupiah($opsi->harga_tambahan) }} per produk)@endif</p>
                @endforeach
                <p>{{ $rupiah($item->harga_satuan) }} per produk × {{ $item->jumlah }} = <strong>{{ $rupiah($item->subtotal) }}</strong></p>
            </li>
        @endforeach
    </ul>
    <dl><div><dt>Subtotal produk</dt><dd>{{ $rupiah($pesanan->subtotal) }}</dd></div>
        <div><dt>Ongkos antar</dt><dd>{{ $rupiah($pesanan->ongkir) }}</dd></div>
        @if($selisih)<div><dt>{{ $selisih > 0 && $selisih < 1000 ? 'Pembulatan' : 'Selisih total tercatat' }}</dt><dd>{{ $rupiah($selisih) }}</dd></div>@endif
        @if($pesanan->penyesuaian)<div><dt>Penyesuaian pesanan</dt><dd>{{ $rupiah($pesanan->penyesuaian) }}</dd></div>@endif
        <div class="bk-estimate-total"><dt>Total pesanan</dt><dd>{{ $rupiah($pesanan->total) }}</dd></div>
    </dl>
</section>

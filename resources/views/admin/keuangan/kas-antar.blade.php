@extends('layouts.admin')
@section('title','Pembayaran dan setoran antar')
@section('content')
<section class="bk-owner-list bk-cash-desk grid gap-5 p-4"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<header><p class="kc-kicker">Operasional · Pemeriksaan kas</p><h1>Pembayaran dan setoran antar</h1>
<nav aria-label="Langsung ke kelompok pemeriksaan" class="bk-history-filters"><a href="#antrean-bank">Konfirmasi bank · {{ $queue->total() }}</a><a href="#setoran-antar">Setoran antar · {{ $rincian->total() }}</a></nav></header>
<a class="bk-secondary" href="{{ route('admin.rekonsiliasi.index') }}">Periksa rekonsiliasi mutasi bank</a>
<h2 id="antrean-bank">Konfirmasi pelanggan menunggu pemeriksaan (semua tanggal)</h2>
@forelse($queue as $b)
<form method="post" action="{{ route('admin.pesanan.verify-payment',$b->pesanan_id) }}" class="grid gap-2 border p-3" data-ledger>@csrf
    <p>{{ $b->nomor }} · {{ $b->jenis }} · Tagihan persis Rp{{ number_format($b->nominal_diharapkan,0,',','.') }} · {{ $b->nama_bank }} {{ $b->nomor_rekening }} · {{ $b->atas_nama }}</p>
    @if($b->bukti_id)<a href="{{ route('admin.bukti-transfer',[$b->pesanan_id,$b->id]) }}" target="_blank" rel="noopener noreferrer">Lihat bukti privat (bukan verifikasi)</a>@endif
    <input type="hidden" name="pembayaran_id" value="{{ $b->id }}">
    <label>Referensi mutasi aktual <input required name="referensi" maxlength="100"></label>
    <label>Nominal masuk aktual <input required name="nominal" type="number" min="1"></label>
    <button class="btn-primary">Catat mutasi yang sudah diperiksa</button>@include('partials.ledger-feedback')
</form>@empty<p>Tidak ada konfirmasi baru.</p>@endforelse
{{ $queue->fragment('antrean-bank')->links('partials.pagination') }}
<h2 id="setoran-antar">Setoran uang pengantaran</h2>
<p>Catat uang yang diserahkan kurir kepada kasir. Setoran ini bukan pembayaran baru dari pelanggan.</p>
<form class="bk-product-editor grid gap-2" action="{{ route('kas-antar.index') }}#setoran-antar" data-pending="Memuat agenda…">
<input type="hidden" name="antrean" value="{{ $queue->currentPage() }}">
@if(request()->filled('petugas_id'))<input type="hidden" name="petugas_id" value="{{ request('petugas_id') }}">@endif
<label>Tanggal penerimaan pelanggan (WIB) <input required type="date" name="tanggal" value="{{ $tanggal }}"></label><button>Tampilkan</button></form>
@if(request()->filled('petugas_id'))<p>Filter setoran: petugas #{{ request('petugas_id') }}. <a class="bk-secondary" href="{{ route('kas-antar.index',['tanggal'=>$tanggal,'antrean'=>$queue->currentPage()]) }}#setoran-antar">Tampilkan semua petugas</a></p>@endif
@if($ringkasan->isNotEmpty())
<details class="bk-cash-summary"><summary>Ringkasan per petugas · {{ $ringkasan->count() }} petugas</summary>
@foreach($ringkasan as $recap)<section><h3>Petugas #{{ $recap->petugas_id }} · {{ $recap->pembayaran }} pembayaran</h3>
<dl>@foreach(['tagihan_setor'=>'Tagihan setor','disetor'=>'Tercatat disetor','kurang'=>'Kurang','lebih'=>'Lebih'] as $key=>$caption)<div><dt>{{ $caption }}</dt><dd>Rp{{ number_format($recap->$key,0,',','.') }}</dd></div>@endforeach</dl>@unless(request()->filled('petugas_id'))<a class="bk-secondary" href="{{ route('kas-antar.index',['tanggal'=>$tanggal,'petugas_id'=>$recap->petugas_id,'antrean'=>$queue->currentPage()]) }}#setoran-antar">Periksa setoran petugas #{{ $recap->petugas_id }}</a>@endunless</section>@endforeach</details>
@endif
@forelse($rincian as $r)
<article id="setoran-{{ $r->pembayaran_id }}" class="bk-task grid gap-2"><header><h3>{{ $r->nomor }}</h3><span class="bk-order-status">{{ ucfirst(str_replace('_',' ',$r->status_setoran)) }}</span></header><p>Petugas #{{ $r->petugas_id }} · Tagihan setor Rp{{ number_format($r->tagihan_setor,0,',','.') }} · Sudah disetor Rp{{ number_format($r->sudah_disetor,0,',','.') }}</p>
@if($r->jenis_jaminan === 'deposit')
<p>Deposit Rp{{ number_format($r->nominal_jaminan,0,',','.') }} adalah jaminan terpisah. Jangan masukkan deposit ke hitungan setoran pembayaran ini.</p>
<a class="bk-secondary" href="{{ route('retur.inspeksi',$r->pesanan_id) }}#penutupan">Periksa jaminan sewa · {{ $r->nomor }}</a>
@endif
<a class="bk-secondary" href="{{ route('kas-antar.history',['id'=>$r->pembayaran_id,'halaman_setoran'=>$rincian->currentPage(),'antrean'=>$queue->currentPage()] + request()->only('petugas_id')) }}">Riwayat hitung dan koreksi · {{ $r->nomor }}</a>
@if((int)$r->petugas_id !== auth('staff')->id())
<form method="post" action="{{ route('kas-antar.record',$r->pembayaran_id) }}" data-ledger data-cash-count data-expected="{{ $r->tagihan_setor }}" data-recorded="{{ $r->status_setoran === 'belum_disetor' ? 0 : 1 }}" class="grid gap-2">@csrf
    <input type="hidden" name="kunci" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="sebelum" value="{{ $r->sudah_disetor }}">
    <label>Total kumulatif setelah dihitung <input required type="number" min="0" max="4294967295" step="1" name="sesudah"></label>
    <p class="bk-count-preview" hidden data-count-preview role="status"></p>
    <label>Catatan selisih atau koreksi <input name="catatan" maxlength="200" @required($r->status_setoran !== 'belum_disetor')></label>
    <small>Catatan wajib jika jumlah berbeda dari tagihan setor atau mengoreksi setoran sebelumnya.</small>
    <label><input required type="checkbox" name="diperiksa_fisik" value="1"> Kas fisik sudah diperiksa</label>
    <button class="btn-primary">Catat hasil hitung</button>@include('partials.ledger-feedback')
</form>@else<p>Setoran ini harus diperiksa oleh petugas lain.</p>@endif</article>
@empty<p>Tidak ada pembayaran tunai antar pada tanggal ini.</p>@endforelse
{{ $rincian->fragment('setoran-antar')->links('partials.pagination') }}

</section>
@endsection

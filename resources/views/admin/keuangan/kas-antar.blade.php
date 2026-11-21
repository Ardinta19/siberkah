@extends('layouts.admin')
@section('title','Pembayaran dan setoran antar')
@section('content')
<section class="grid gap-5 p-5"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<h1 class="text-2xl font-bold">Pembayaran dan setoran antar</h1>
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
<form data-pending="Memuat agenda…"><label>Tanggal penerimaan pelanggan (WIB) <input required type="date" name="tanggal" value="{{ $tanggal }}"></label><button>Tampilkan</button></form>
@if($ringkasan->isNotEmpty())
<details class="bk-cash-summary"><summary>Ringkasan per petugas · {{ $ringkasan->count() }} petugas</summary>
@foreach($ringkasan as $recap)<section><h3>Petugas #{{ $recap->petugas_id }} · {{ $recap->pembayaran }} pembayaran</h3>
<dl>@foreach(['tagihan_setor'=>'Tagihan setor','disetor'=>'Tercatat disetor','kurang'=>'Kurang','lebih'=>'Lebih'] as $key=>$caption)<div><dt>{{ $caption }}</dt><dd>Rp{{ number_format($recap->$key,0,',','.') }}</dd></div>@endforeach</dl></section>@endforeach</details>
@endif
@forelse($rincian as $r)
<article class="grid gap-2 border p-3"><p>{{ $r->nomor }} · Petugas #{{ $r->petugas_id }} · {{ ucfirst(str_replace('_',' ',$r->status_setoran)) }} · Tagihan setor Rp{{ number_format($r->tagihan_setor,0,',','.') }} · Sudah disetor Rp{{ number_format($r->sudah_disetor,0,',','.') }}</p>
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

@extends('layouts.admin')
@section('content')
<section class="grid gap-5 p-5"><x-back-link :href="route('dashboard.hari-ini')">Kembali ke dasbor</x-back-link>
<h1 class="text-2xl font-bold">Pembayaran dan setoran antar</h1>
<h2>Konfirmasi pelanggan menunggu pemeriksaan (semua tanggal)</h2>
@forelse($queue as $b)
<form method="post" action="{{ route('admin.pesanan.verify-payment',$b->pesanan_id) }}" class="grid gap-2 border p-3" data-ledger>@csrf
    <p>{{ $b->nomor }} · {{ $b->jenis }} · Tagihan persis Rp{{ number_format($b->nominal_diharapkan,0,',','.') }} · {{ $b->nama_bank }} {{ $b->nomor_rekening }} · {{ $b->atas_nama }}</p>
    @if($b->bukti_id)<a href="{{ route('admin.bukti-transfer',[$b->pesanan_id,$b->id]) }}" target="_blank" rel="noopener noreferrer">Lihat bukti privat (bukan verifikasi)</a>@endif
    <input type="hidden" name="pembayaran_id" value="{{ $b->id }}">
    <label>Referensi mutasi aktual <input required name="referensi" maxlength="100"></label>
    <label>Nominal masuk aktual <input required name="nominal" type="number" min="1"></label>
    <button class="btn-primary">Catat mutasi yang sudah diperiksa</button><output role="status"></output>
</form>@empty<p>Tidak ada konfirmasi baru.</p>@endforelse
{{ $queue->links('partials.pagination') }}
<h2>Setoran uang pengantaran</h2>
<form data-pending="Memuat agenda…"><label>Tanggal penerimaan pelanggan (WIB) <input required type="date" name="tanggal" value="{{ $tanggal }}"></label><button>Tampilkan</button></form>
@forelse($rincian as $r)
<article class="grid gap-2 border p-3"><p>{{ $r->nomor }} · Petugas #{{ $r->petugas_id }} · {{ $r->status_setoran }} · Tagihan setor Rp{{ number_format($r->tagihan_setor,0,',','.') }} · Sudah disetor Rp{{ number_format($r->sudah_disetor,0,',','.') }}</p>
@if((int)$r->petugas_id !== auth('staff')->id())
<form method="post" action="{{ route('kas-antar.record',$r->pembayaran_id) }}" data-ledger class="grid gap-2">@csrf
    <input type="hidden" name="kunci" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="sebelum" value="{{ $r->sudah_disetor }}">
    <label>Total kumulatif setelah dihitung <input required type="number" min="0" name="sesudah"></label>
    <label>Catatan selisih atau koreksi <input name="catatan" maxlength="200"></label>
    <label><input required type="checkbox" name="diperiksa_fisik" value="1"> Kas fisik sudah diperiksa</label>
    <button class="btn-primary">Catat hasil hitung</button><output role="status"></output>
</form>@endif</article>
@empty<p>Tidak ada pembayaran tunai antar pada tanggal ini.</p>@endforelse
{{ $rincian->links('partials.pagination') }}
@include('partials.submit-ledger')
</section>
@endsection

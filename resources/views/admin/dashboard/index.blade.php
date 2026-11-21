@extends('layouts.admin')
@section('title','Dasbor operasional · SIBERKAH')
@section('page-title','Ruang kerja')
@section('content')
<section class="bk-ops" aria-labelledby="ops-title">
    <header><p class="kc-kicker">Berkah Kreatif · {{ auth('staff')->user()->isOwner() ? 'Owner' : 'Admin' }}</p>
        <h1 id="ops-title">Dasbor operasional</h1>
        <p>Agenda {{ \Illuminate\Support\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }} · WIB</p>
    </header>
    @if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
    <section aria-labelledby="ops-counts"><h2 id="ops-counts">Ringkasan pekerjaan</h2>
        <dl class="bk-ops-grid">
            @foreach([
                'verifikasi'=>['Menunggu verifikasi','Pesanan dengan konfirmasi pelanggan · semua tanggal'],
                'antar'=>['Siap diantar','Pesanan siap dengan tanggal serah hari ini'],
                'jemput'=>['Penjemputan sewa','Sewa sudah diserahkan dengan tanggal serah hari ini'],
                'produksi'=>['Sedang diproduksi','Pesanan dalam produksi dengan tanggal serah hari ini'],
            ] as $key=>[$label,$description])
                <div><dt>{{ $label }}</dt><dd><strong>{{ number_format($ringkasan[$key],0,',','.') }}</strong><small>{{ $description }}</small></dd></div>
            @endforeach
        </dl>
    </section>
    <section aria-labelledby="ops-alerts"><h2 id="ops-alerts">Unit yang perlu ditindaklanjuti</h2>
        @forelse($peringatan as $unit)
            <p class="bk-ops-alert"><strong>Unit {{ $unit->kode }}</strong> belum kembali dari {{ $unit->pesanan_sekarang }}; diperlukan untuk {{ $unit->pesanan_berikut }} pada {{ $unit->tanggal }}.</p>
        @empty<p>Belum ada benturan pengembalian unit untuk reservasi hari ini atau besok yang terdeteksi.</p>@endforelse
    </section>
    <nav aria-label="Buka area kerja" class="bk-ops-links"><h2>Buka area kerja</h2>
        @if(config('checkout.staging') && app()->environment('testing','staging','local'))
            <a href="{{ route('admin.kalender',['tanggal'=>$tanggal]) }}">Periksa kalender dan kapasitas</a>
            <a href="{{ route('kas-antar.index',['tanggal'=>$tanggal]) }}">Periksa pembayaran dan setoran antar</a>
        @endif
        <a href="{{ route('staf.kurir.index',['tanggal'=>$tanggal,'tugas'=>'antar']) }}">Agenda antar hari ini</a>
        <a href="{{ route('staf.kurir.index',['tanggal'=>$tanggal,'tugas'=>'jemput']) }}">Agenda jemput hari ini</a>
        <a href="{{ route('staf.produksi.index',['tahap'=>'sedang_diproduksi']) }}">Produksi berjalan · semua tanggal</a>
    </nav>
</section>
@endsection

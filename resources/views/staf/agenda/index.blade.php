@extends('layouts.staff')
@section('title', $judul.' · SIBERKAH')
@section('page-title', 'Ruang kerja staf')
@section('content')
<section class="bk-agenda" aria-labelledby="agenda-title">
    <p class="kc-kicker">Berkah Kreatif · Operasional</p><h2 id="agenda-title">{{ $judul }}</h2>
    @if(session('status')) <p role="status" class="bk-notice">{{ session('status') }}</p> @endif
    @if($errors->any())<p role="alert" class="bk-notice">{{ $errors->first() }}</p>@endif
    @isset($tanggal)
        <form method="get" action="{{ route('staf.kurir.index') }}" class="bk-agenda-tools" data-pending="Memuat agenda…">
            @if(request()->filled('tugas'))<input type="hidden" name="tugas" value="{{ request('tugas') }}">@endif
            <label>Tanggal WIB <input type="date" required name="tanggal" value="{{ $tanggal }}"></label>
            <button class="bk-secondary">Tampilkan</button>
            @if($tanggal !== today('Asia/Jakarta')->toDateString())<a class="bk-secondary" href="{{ route('staf.kurir.index',request()->only('tugas')) }}">Hari ini</a>@endif
        </form>
        <nav class="bk-history-filters" aria-label="Jenis tugas kurir">
            @foreach([''=>'Semua tugas','antar'=>'Antar','ambil'=>'Ambil di toko','jemput'=>'Jemput'] as $key=>$label)
                <a href="{{ route('staf.kurir.index',array_filter(['tanggal'=>$tanggal,'tugas'=>$key])) }}#daftar-tugas" @if((request('tugas') ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endisset
    <div id="daftar-tugas"><p class="bk-agenda-count">{{ $pesanan->total() }} {{ isset($tanggal) ? 'tugas' : 'pesanan' }}@isset($tanggal) · {{ \Illuminate\Support\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') }} WIB @endisset</p>
    @forelse($pesanan as $p)
        <article class="bk-task" aria-labelledby="tugas-{{ $p->id }}"><header><h3 id="tugas-{{ $p->id }}">{{ $p->nomor }}</h3><span class="bk-order-status">{{ str_replace('_',' ',$p->status) }}</span></header>
        <p>Tanggal serah · {{ $p->tanggal_serah->format('d/m/Y') }}</p>
        @if($produksi ?? false)
            <ul>@foreach($p->rincian as $item)<li>{{ $item->jumlah }} × {{ $item->nama_produk }} {{ $item->nama_varian }} {{ $item->tulisan_kustom }}
                @foreach($item->opsi as $opt)<span>{{ $opt->nama_opsi }}: {{ $opt->label_nilai }} {{ $opt->teks_bebas }}</span>@endforeach</li>@endforeach</ul>
            @if($p->papan)<div>Papan {{ $p->papan->metode }}: {{ $p->papan->judul }} · {{ $p->papan->penerima }} · {{ $p->papan->pengirim }}</div>@endif
            @if($p->catatan_pelanggan)<div>Catatan: {{ $p->catatan_pelanggan }}</div>@endif
        @else
            <div>{{ ucfirst($p->tugas) }} · {{ $p->jadwal ? substr($p->jadwal,11,5).' WIB' : 'Jam belum ditetapkan' }} · {{ $p->nomor_wa_pemesan }}
            @if($p->peta) {{ $p->alamat_antar }} · <a href="{{ $p->peta }}" target="_blank" rel="noreferrer noopener">Buka peta</a>@endif</div>
        @endif
        @if(!($produksi ?? false) && config('checkout.staging') && app()->environment('testing','staging','local') && $p->buket_online && $p->status === 'siap' && $p->tanggal_serah->isToday())
            <form method="post" action="{{ route('toko.serah', $p->id) }}" class="grid gap-3 p-3" data-ledger>
                @csrf
                <label><input type="checkbox" required name="barang_diperiksa" value="1"> Barang diperiksa</label>
                <label><input type="checkbox" required name="diterima_pembeli" value="1"> Pembeli telah menerima barang</label>
                @if($p->tunai_saat_serah)
                    <label>Uang diterima (saldo Rp{{ number_format($p->saldo,0,',','.') }}) <input required type="number" min="1" name="nominal_tunai"></label>
                    <label><input type="checkbox" required name="tunai_diterima" value="1"> Uang tunai sudah diterima</label>
                @endif
                <button class="btn-primary">Catat serah terima {{ $p->nomor }}</button><output role="status"></output>
            </form>
        @endif
        @if($produksi ?? false)
            <form data-pending="Memperbarui produksi…" method="post" class="flex gap-2 py-2">
                @csrf
                <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                @if($p->status === 'dikonfirmasi')
                    <button formaction="{{ route('staf.produksi.start') }}" class="btn-outline">Mulai produksi custom</button>
                @endif
                <button formaction="{{ route('staf.produksi.mark-ready') }}" class="btn-primary">Tandai siap</button>
            </form>
        @endif
        </article>
    @empty
        <div class="kc-empty"><p>Tidak ada pesanan dalam antrean.</p>
            @if(isset($tanggal) && request()->filled('tugas'))<a class="bk-secondary" href="{{ route('staf.kurir.index',['tanggal'=>$tanggal]) }}#daftar-tugas">Lihat semua tugas pada tanggal ini</a>@endif
        </div>
    @endforelse
    {{ $pesanan->fragment('daftar-tugas')->links('partials.pagination') }}</div>
    @include('partials.submit-ledger')
</section>
@endsection

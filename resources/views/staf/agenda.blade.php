@extends('layouts.staff')
@section('content')
<section class="p-6">
    <h1 class="mb-4 text-2xl font-bold">{{ $judul }}</h1>
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @isset($tanggal)<form><label>Tanggal WIB <input type="date" required name="tanggal" value="{{ $tanggal }}"></label><button>Tampilkan</button></form>@endisset
    @forelse($pesanan as $p)
        <p class="border-b py-3">{{ $p->nomor }} · {{ $p->tanggal_serah->format('d/m/Y') }} · {{ str_replace('_', ' ', $p->status) }}</p>
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
            <form method="post" class="flex gap-2 py-2">
                @csrf
                <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                @if($p->status === 'dikonfirmasi')
                    <button formaction="{{ route('staf.produksi.start') }}" class="btn-outline">Mulai produksi custom</button>
                @endif
                <button formaction="{{ route('staf.produksi.mark-ready') }}" class="btn-primary">Tandai siap</button>
            </form>
        @endif
    @empty
        <p>Tidak ada pesanan dalam antrean.</p>
    @endforelse
    {{ $pesanan->links() }}
    @include('staf.submit-ledger')
</section>
@endsection

@extends('layouts.guest')
@section('title', 'Katalog Produk · SIBERKAH')

@section('content')
<section class="min-h-screen bg-[#fffdf9] text-[#4c0519]">
    <div class="container-siberkah py-8">
        <p class="text-xs font-bold uppercase tracking-widest text-primary">Berkah Kreatif / Katalog Produk</p>
        <h1 class="mt-2 font-display text-4xl font-bold text-primary">Koleksi Estetik</h1>
        <p class="mt-2 text-outline-600">Pilih papan sewa untuk hari istimewa Anda, atau temukan buket untuk dibawa pulang.</p>
        <form class="my-6 flex flex-wrap items-end gap-3 rounded-xl border border-[#eadbd6] bg-[#faf5f0] p-4 text-sm" action="{{ route('catalog.index') }}">
            @foreach(request()->only(['kategori']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama produk atau kategori" aria-label="Cari produk" class="min-w-0 w-full max-w-lg rounded-md border border-outline-200 bg-white px-4">
            <label>Jenis produk <select name="jenis"><option value="">Semua jenis</option>@foreach(['sewa'=>'Sewa','jual'=>'Siap jual','custom'=>'Custom'] as $jenis=>$nama)<option value="{{ $jenis }}" @selected($filterJenis === $jenis)>{{ $nama }}</option>@endforeach</select></label>
            <label>Tanggal sewa <input type="date" name="tanggal" value="{{ request('tanggal') }}"></label>
            <label>Urutan <select name="urut"><option value="">Katalog</option><option value="harga_naik" @selected(request('urut') === 'harga_naik')>Harga terendah</option><option value="harga_turun" @selected(request('urut') === 'harga_turun')>Harga tertinggi</option></select></label>
            <button class="btn-primary">Cari</button>
        </form>
        <nav aria-label="Kategori produk" class="flex gap-2 overflow-x-auto pb-4">
            @foreach(collect([null => 'Semua'])->union($kategoris->pluck('nama', 'id')) as $id => $nama)
                <a href="{{ route('catalog.index', array_filter(array_merge(request()->only(['q','jenis','tanggal','urut']), ['kategori' => $id]), fn ($v) => $v !== '')) }}" @class(['shrink-0 rounded-full border px-4 py-2 text-sm font-semibold', 'bg-primary text-white border-primary' => (string)$filterKategori === (string)$id, 'bg-white border-outline-200' => (string)$filterKategori !== (string)$id])>{{ $nama }}</a>
            @endforeach
        </nav>
        <p class="mt-5 text-sm font-medium" role="status">{{ $produk->total() }} produk ditemukan @if($filterTanggal) · Tersedia untuk sewa pada {{ $filterTanggal }} @endif</p>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($produk as $p)
                <article class="flex flex-col overflow-hidden rounded-xl border border-[#eadbd6] bg-white shadow-sm">
                    <div class="relative grid h-52 place-items-center bg-primary-50">
                        @if($p->foto_utama)
                            <img src="{{ asset('storage/'.$p->foto_utama) }}" class="h-52 w-full object-cover" alt="{{ $p->nama }}" loading="lazy">
                        @else
                            <span aria-hidden="true" class="font-display text-6xl text-primary-200">✿</span>
                        @endif
                        <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-bold text-primary">{{ ucfirst($p->jenis) }}</span>
                        <button type="button" data-favorite="{{ $p->id }}" aria-label="Simpan {{ $p->nama }} ke favorit" aria-pressed="false" class="absolute right-3 top-3 rounded-full bg-white px-3 py-2 text-primary">♡</button>
                        <span class="absolute bottom-3 right-3 rounded bg-primary px-2 py-1 text-xs text-white">{{ $p->kategori?->nama }}</span>
                    </div>
                    <div class="flex flex-1 flex-col gap-2 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary">{{ $p->jenis === 'sewa' ? 'Koleksi sewa' : 'Koleksi buket' }}</p>
                        <h2 class="font-display text-2xl font-bold">{{ $p->nama }}</h2>
                        <details class="mb-3 text-sm text-outline-600"><summary class="cursor-pointer">Ringkasan · {{ $p->varianProduk->count() }} varian</summary>{{ $p->deskripsi ?: 'Belum ada deskripsi.' }}</details>
                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-outline-200 pt-4">
                            <div><small class="block text-xs">{{ $p->jenis === 'sewa' ? 'Tarif dasar sewa' : 'Harga dasar' }}</small><strong class="text-primary">Rp{{ number_format($p->harga_dasar, 0, ',', '.') }}</strong></div>
                            <a class="btn-primary" href="{{ route('catalog.show', ['slug' => $p->slug, 'tanggal' => $filterTanggal]) }}">Lihat detail</a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-md border border-outline-200 bg-white p-5">Tidak ada produk yang ditemukan. <a class="text-primary underline" href="{{ route('catalog.index') }}">Lihat semua produk</a></p>
            @endforelse
        </div>
        {{ $produk->links() }}
    </div>
</section>
@endsection

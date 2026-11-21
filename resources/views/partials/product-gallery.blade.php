@php
    $photos = $gambarProduk->pluck('teks_alternatif','jalur_berkas');
    if ($produk->foto_utama) {
        $photos = collect([$produk->foto_utama => $photos->get($produk->foto_utama)])->union($photos);
    }
@endphp
<section class="bk-gallery" aria-label="Galeri {{ $produk->nama }}" data-gallery>
    @forelse($photos as $path => $alt)
        <figure data-photo><a href="{{ asset('storage/'.$path) }}" aria-label="Perbesar foto {{ $loop->iteration }}: {{ $alt ?: $produk->nama }}"><img loading="{{ $loop->first ? 'eager' : 'lazy' }}" alt="{{ $alt ?: $produk->nama }}" src="{{ asset('storage/'.$path) }}"></a></figure>
    @empty
        <div class="kc-media"><img class="kc-monogram" src="{{ asset('images/berkah-kreatif.webp') }}" alt="Foto produk belum tersedia"></div>
    @endforelse
    @if($photos->isNotEmpty())
        <div hidden data-gallery-bar class="bk-photo-bar">
            @if($photos->count()>1)<button type="button" data-prev aria-label="Foto sebelumnya">‹</button>@endif
            <span role="status" data-count></span>
            @if($photos->count()>1)<button type="button" data-next aria-label="Foto berikutnya">›</button>@endif
            <button type="button" data-enlarge aria-haspopup="dialog">Perbesar</button>
        </div>
        @if($photos->count()>1)<p class="bk-swipe-tip">Geser foto ke kiri atau kanan untuk menjelajah.</p>@endif
        <dialog class="bk-lightbox" aria-label="Foto {{ $produk->nama }}">
            <header><p class="kc-kicker">Galeri produk</p><button type="button" data-close aria-label="Tutup foto">×</button></header>
            <img data-large alt=""><footer class="bk-photo-bar">
                @if($photos->count()>1)<button type="button" data-prev aria-label="Foto sebelumnya">‹</button>@endif
                <span role="status" data-count></span>
                @if($photos->count()>1)<button type="button" data-next aria-label="Foto berikutnya">›</button>@endif
            </footer>
        </dialog>
    @endif
</section>

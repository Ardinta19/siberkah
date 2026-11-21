@php
    $mediaByPath = $gambarProduk->keyBy('jalur_berkas');
    $variantNames = $varian->pluck('nama','id');
    $photos = $gambarProduk->pluck('teks_alternatif','jalur_berkas');
    if ($produk->foto_utama) {
        $photos = collect([$produk->foto_utama => $photos->get($produk->foto_utama)])->union($photos);
    }
@endphp
<section class="bk-gallery" aria-label="Galeri {{ $produk->nama }}" data-gallery>
    @forelse($photos as $path => $alt)
        @php($media = $mediaByPath->get($path))
        @php($variantLabel = $variantNames->get($media?->varian_produk_id))
        <figure data-photo data-media-id="{{ $media?->id }}" data-variant-label="{{ $variantLabel }}"><a href="{{ asset('storage/'.$path) }}" aria-label="Perbesar foto {{ $loop->iteration }}: {{ $alt ?: $produk->nama }}"><img loading="{{ $loop->first ? 'eager' : 'lazy' }}" alt="{{ $alt ?: $produk->nama }}" src="{{ asset('storage/'.$path) }}"></a>@if($variantLabel)<figcaption class="bk-photo-caption">Varian: {{ $variantLabel }}</figcaption>@endif</figure>
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
        @if($photos->count()>1)
            <div hidden class="bk-filmstrip" data-filmstrip role="group" aria-label="Pilih foto produk">
                @foreach($photos as $path => $alt)
                    <button type="button" data-photo-index="{{ $loop->index }}" aria-label="Pilih foto {{ $loop->iteration }}: {{ $alt ?: $produk->nama }}"><img loading="lazy" width="56" height="56" alt="" src="{{ asset('storage/'.$path) }}"></button>
                @endforeach
            </div>
        @endif
        @if($photos->count()>1)<p class="bk-swipe-tip">Geser foto ke kiri atau kanan untuk menjelajah.</p>@endif
        <dialog class="bk-lightbox" aria-label="Foto {{ $produk->nama }}">
            <header><p class="kc-kicker">Galeri produk</p><button type="button" data-close aria-label="Tutup foto">×</button></header>
            <img data-large alt=""><p hidden data-large-variant class="bk-photo-caption"></p><footer class="bk-photo-bar">
                @if($photos->count()>1)<button type="button" data-prev aria-label="Foto sebelumnya">‹</button>@endif
                <span role="status" data-count></span>
                @if($photos->count()>1)<button type="button" data-next aria-label="Foto berikutnya">›</button>@endif
            </footer>
        </dialog>
    @endif
</section>

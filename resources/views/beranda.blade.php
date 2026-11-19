@extends('layouts.guest')

@section('title', 'Beranda SIBERKAH')

@section('styles')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
@endsection

@section('scripts')
    @push('scripts')
        <script src="{{ asset('js/micro-interactions.js') }}"></script>
    @endpush
@endsection

@section('content')
<div class="py-12">
    <div class="container-siberkah">

        <!-- Header Section -->
        <div class="mb-12 animate-slide-up">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-display-lg-mobile sm:text-display-lg font-bold tracking-tight text-primary-900 sm:text-6xl dark:text-primary-50">
                        Sewa Papan Berkah Kreatif
                    </h1>
                    <p class="font-body text-body-md text-neutral-600 dark:text-neutral-400 mb-6 max-w-2xl">
                        Platform penyewaan perlengkapan kreatif, bunga segar/artificial, standing mirror sign, dan papon akrilik wisuda estetik dari Berkah Kreatif
                    </p>
                </div>

                <!-- CTA Search Card -->
                <div class="rounded-2xl p-6 border border-primary-200 bg-primary-50/50 dark:border-primary-950/20 dark:bg-primary-950/20">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-500 shrink-0 text-[20px]">search</span>
                        <input
                            type="text"
                            id="searchBar"
                            class="flex-1 bg-white dark:bg-neutral-900 border border-primary-300 rounded-xl py-3 pl-8 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                            placeholder="Cari produk... (nama, kategori)"
                            aria-label="Cari produk"
                        >
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            onclick="searchBar.focus()"
                            aria-label="Bersihkan pencarian"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="section-header mb-8">
            <h2 class="section-title">Koleksi Kami</h2>
            <p class="section-subtitle text-neutral-600 dark:text-neutral-400">Pilih kategori di bawah untuk menemukan produk impian Anda</p>
        </div>

        <!-- Filter Categories -->
        <div class="flex flex-wrap gap-2 sm:gap-3 mb-8" id="filterCategories" role="group" aria-label="Filter kategori produk">
            <button class="btn btn-sm btn-primary filter-btn active" data-filter="all" aria-pressed="true">Semua</button>
            @foreach($kategoris as $k)
                <button class="btn btn-sm btn-outline filter-btn" data-filter="{{ $k->slug }}" aria-pressed="false">{{ $k->nama }}</button>
            @endforeach
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6" id="katalogGrid" role="list">
            @foreach($produk as $p)
                <article class="card lifted group hover:shadow-xl hover-transition cursor-pointer reveal"
                    role="listitem"
                    data-category="{{ $p->kategori->slug ?? 'all' }}"
                    data-wishlist="{{ $p->id }}"
                    data-quick-view="{{ $p->slug }}"
                    data-tilt="8">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-lg bg-primary-50/50 dark:bg-primary-950/50">
                        <img
                            src="{{ asset('storage/' . $p->gambar_utama) }}"
                            alt="{{ $p->nama }}"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 ease-out loading=lazy"
                            loading="lazy"
                            aria-describedby="produk-{{ $p->id }}"
                        >

                        <!-- Wishlist Button -->
                        <button
                            class="absolute top-3 right-3 btn btn-icon-sm btn-ghost bg-white/90 backdrop-blur-sm group-hover:scale-110 transition-transform"
                            data-wishlist="{{ $p->id }}"
                            aria-label="Tambah ke wishlist"
                        >
                            <span class="material-symbols-outlined text-[20px]">favorite_border</span>
                        </button>

                        <!-- Quick View Button -->
                        <button
                            class="absolute bottom-3 right-3 btn btn-icon-sm btn-ghost bg-white/90 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all duration-300"
                            data-quick-view="{{ $p->slug }}"
                            aria-label="Lihat detail cepat"
                        >
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </button>

                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            @if($p->status === 'tersedia')
                                <span class="status-badge status-available" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-primary-500">circle</span>
                                    Tersedia <span class="text-xs">{{ $p->tersedia ?? 3 }} Unit</span>
                                </span>
                            @elseif($p->status === 'reservasi')
                                <span class="status-badge status-reserving" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-warning-500">calendar</span>
                                    Reservasi
                                </span>
                            @elseif($p->status === 'habis')
                                <span class="status-badge status-out-of-stock" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-error-500">cancel</span>
                                    Habis
                                </span>
                            @elseif($p->status === 'maintenance')
                                <span class="status-badge status-maintenance" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-accent-500">wrench</span>
                                    Maintenance
                                </span>
                            @elseif($p->status === 'damaged')
                                <span class="status-badge status-damaged" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-error-500">bug</span>
                                    Rusak
                                </span>
                            @else
                                <span class="status-badge status-custom" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-on-surface-500">more_horiz</span>
                                    Custom
                                </span>
                            @endif

                            @if($p->is_baru)
                                <span class="status-badge status-new" style="animation-delay: 200ms;">Baru</span>
                            @endif
                            @if($p->is_promo)
                                <span class="status-badge status-promo" style="animation-delay: 300ms;">Promo</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 flex flex-col flex-1">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-display text-display-lg-mobile sm:text-display-lg font-bold tracking-tight text-primary-900 sm:text-6xl dark:text-primary-50 line-clamp-2 mb-1">{{ $p->nama }}</h3>
                            <div class="flex items-center gap-1.5">
                                @if($p->varianProduk->where('aktif', true)->count() > 0)
                                    <span class="bg-primary-100/30 border border-primary-200 text-primary-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                        <span class="material-symbols-outlined text-[10px]">tune</span>
                                        {{ count($p->varianProduk->where('aktif', true)) }} Varian
                                    </span>
                                @endif
                                @if($p->is_baru)
                                    <span class="badge badge-accent badge-sm">Baru</span>
                                @endif
                                @if($p->is_promo)
                                    <span class="badge badge-warning badge-sm">Promo</span>
                                @endif
                            </div>
                        </div>

                        <p class="font-body text-body-sm text-on-surface-variant line-clamp-2 mb-4">{{ substr(strip_tags($p->deskripsi ?? ''), 0, 120) }}...</p>

                        <div class="flex items-center justify-between pt-3 border-t border-outline-variant">
                            <div class="text-right">
                                <p class="font-mono text-primary-600 dark:text-primary-400">
                                    Rp{{ number_format($p->harga_dasar, 0, ',', '.') }}
                                </p>
                                <p class="font-body text-caption text-on-surface-variant">
                                    {{ $p->jenis === 'sewa' ? 'Disewakan' : ($p->jenis === 'jual' ? 'Dijual' : 'Custom') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button class="btn btn-icon-sm btn-ghost" data-wishlist="{{ $p->id }}" aria-label="Wishlist">
                                    <span class="material-symbols-outlined text-[20px]">favorite_border</span>
                                </button>
                                <a href="{{ route('produk.detail', $p->slug) }}" class="btn btn-primary btn-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                    <span>Sewa Unit</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Load More Button -->
        <div class="mt-12 text-center">
            <button id="loadMoreBtn" class="btn btn-primary w-full py-3">
                <span>Load More</span>
                <span class="material-symbols-outlined text-[18px]">keyboard_double_arrow_down</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize Beranda-specific interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize filter functionality
    initCategoryFilter();

    // Initialize wishlist buttons
    initWishlistButtons();

    // Initialize quick view
    initQuickView();

    // Initialize card tilt/magnetic effects
    initCardEffects();

    // Initialize scroll reveal
    MicroInteractions.scroll.reveal(document.querySelectorAll('.reveal'));

    // Initialize counters
    initCounters();

    // Initialize search highlighting
    initSearchHighlight();
});

// Initialize filter functionality
function initCategoryFilter() {
    const filterButtons = document.querySelectorAll('#filterCategories .filter-btn');
    const cards = document.querySelectorAll('#katalogGrid .card');

    if (!filterButtons.length || !cards.length) return;

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btnDataset.filter;

            // Update active state
            filterButtons.forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline');
            btn.setAttribute('aria-pressed', 'true');

            // Filter cards with staggered animation
            cards.forEach((card, index) => {
                const category = cardDataset.category;
                if (filter === 'all' || category === filter) {
                    card.style.display = '';
                    requestAnimationFrame(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0) scale(1)';
                    });
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px) scale(0.95)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
}

// Initialize wishlist buttons
function initWishlistButtons() {
    document.querySelectorAll('[data-wishlist]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const icon = btn.querySelector('.material-symbols-outlined');
            const isActive = btn.classList.toggle('active');

            if (isActive) {
                icon.textContent = 'favorite';
                btn.classList.add('bg-accent/10', 'text-accent');
                btn.classList.remove('bg-white/90', 'text-on-surface-variant');
                showToast({ type: 'success', title: 'Ditambahkan ke Wishlist' });
            } else {
                icon.textContent = 'favorite_border';
                btn.classList.remove('bg-accent/10', 'text-accent');
                btn.classList.add('bg-white/90', 'text-on-surface-variant');
                showToast({ type: 'info', title: 'Dihapus dari Wishlist' });
            }

            // Heartbeat animation
            btn.style.animation = 'heartbeat 800ms ease-in-out';
            setTimeout(() => btn.style.animation = '', 800);
        });
    });
}

// Initialize quick view
function initQuickView() {
    document.querySelectorAll('[data-quick-view]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const slug = btnDataset.quickView;
            showToast({ type: 'info', title: 'Quick view untuk ' + slug });
            // TODO: Implement quick view modal
        });
    });
}

// Initialize card effects
function initCardEffects() {
    // Initialize tilt effect for cards with data-tilt
    document.querySelectorAll('.card[data-tilt]').forEach(card => {
        MicroInteractions.card.tilt(card, cardDataset.tilt);
    });

    // Initialize magnetic effect for cards with data-magnetic
    document.querySelectorAll('[data-magnetic]').forEach(card => {
        MicroInteractions.card.magnetic(card, cardDataset.magnetic);
    });
}

// Initialize counters
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(elDataset.counter) || 0;
                MicroInteractions.counter.animate(el, target, 2000, { decimals: 0 });
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
}

// Initialize search highlighting
function initSearchHighlight() {
    const searchInput = document.getElementById('searchBar');
    if (!searchInput) return;

    let timeoutId;
    searchInput.addEventListener('input', () => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(highlightSearchResults, 150);
    });
}

function highlightSearchResults() {
    const term = document.getElementById('searchBar')?.value?.trim();
    if (!term) {
        document.querySelectorAll('.card h3').forEach(h3 => {
            h3.style.color = '';
            h3.style.textShadow = '';
        });
        return;
    }

    const regex = new RegExp(`(${term})`, 'gi');
    document.querySelectorAll('.card h3').forEach(h3 => {
        h3.innerHTML = h3.textContent.replace(regex, '<span class="text-primary-600 font-bold">$1</span>');
    });
}
</script>
@endpush
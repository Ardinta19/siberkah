@extends('layouts.customer')

@section('title', 'SIBERKAH - Pesanan Saya')

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
                        Pesanan Saya
                    </h1>
                    <p class="font-body text-body-md text-neutral-600 dark:text-neutral-400 mb-6 max-w-2xl">
                        Riwayat pesanan sewa buket & akrilik Anda
                    </p>
                </div>

                <!-- CTA Action -->
                <div class="rounded-2xl p-6 border border-primary-200 bg-primary-50/50 dark:border-primary-950/20 dark:bg-primary-950/20">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-500 shrink-0 text-[20px]">shopping_cart</span>
                        <span class="font-medium text-primary-600">Riwayat lengkap</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="section-header mb-8">
            <h2 class="section-title">Pesanan Saya</h2>
            <p class="section-subtitle text-neutral-600 dark:text-neutral-400">Lihat status dan detail semua pesanan Anda</p>
        </div>

        <!-- Filter & Search -->
        <div class="mb-8 animate-slide-up" style="transition-delay: 100ms;">
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <!-- Filter Status -->
                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-primary filter-status active" data-status="all" aria-pressed="true">Semua</button>
                    <button class="btn btn-sm btn-outline filter-status" data-status="pending" aria-pressed="false">Pending</button>
                    <button class="btn btn-sm btn-outline filter-status" data-status="confirmed" aria-pressed="false">Dikonfirmasi</button>
                    <button class="btn btn-sm btn-outline filter-status" data-status="shipping" aria-pressed="false">Sedang Dikirim</button>
                    <button class="btn btn-sm btn-outline filter-status" data-status="completed" aria-pressed="false">Selesai</button>
                </div>

                <!-- Search Order -->
                <div class="relative flex flex-1 sm:w-auto">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400">search</span>
                    <input
                        type="text"
                        id="orderSearch"
                        class="pl-10 pr-4 py-2.5 px-8 bg-white dark:bg-neutral-900 border border-primary-300 rounded-xl w-full sm:w-auto placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                        placeholder="Cari nomor pesanan..."
                        aria-label="Cari nomor pesanan"
                    >
                </div>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6" id="ordersGrid" role="list">
            @foreach($pesanan as $po)
                <article class="interactive-card group hover-lift cursor-pointer reveal"
                    role="listitem"
                    data-order-id="{{ $po->id }}"
                    data-order-status="{{ $po->status }}"
                    data-tilt="6">
                    <div class="relative aspect-[3/4] overflow-hidden rounded-xl bg-primary-50/50 dark:bg-primary-950/50 p-4 sm:p-5">
                        <!-- Order Status Badge -->
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            @if($po->status === 'pending')
                                <span class="bg-primary-100/30 border border-primary-200 text-primary-800 text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-primary-500">circle</span>
                                    Pending
                                </span>
                            @elseif($po->status === 'confirmed')
                                <span class="bg-primary-100/30 border border-primary-200 text-primary-800 text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-primary-500">check_circle</span>
                                    Dikonfirmasi
                                </span>
                            @elseif($po->status === 'shipping')
                                <span class="bg-primary-100/30 border border-primary-200 text-primary-800 text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-primary-500">send</span>
                                    Dikirim
                                </span>
                            @elseif($po->status === 'completed')
                                <span class="bg-success-100/30 border border-success-200 text-success-800 text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-success-500">check_circle</span>
                                    Selesai
                                </span>
                            @elseif($po->status === 'cancelled')
                                <span class="bg-error-100/30 border border-error-200 text-error-800 text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-error-500">cancel</span>
                                    Dibatalkan
                                </span>
                            @else
                                <span class="bg-outline-100/30 border border-outline-variant text-on-surface-variant text-xs font-medium px-2.5 py-1 rounded-full animate-bounce-in" style="animation-delay: 100ms;">
                                    <span class="material-symbols-outlined text-[12px] text-on-surface-500">more_horiz</span>
                                    {{ ucfirst($po->status) }}
                                </span>
                            @endif
                        </div>

                        <!-- Order Image -->
                        <img
                            src="{{ asset('storage/' . $po->produk->gambar_utama) }}"
                            alt="{{ $po->produk->nama }}"
                            class="w-full h-40 object-cover transition-transform duration-500 group-hover:scale-105 ease-out loading=lazy"
                            loading="lazy"
                        >

                        <!-- Order Info -->
                        <div class="mt-3">
                            <h3 class="font-display font-medium text-primary-600 dark:text-primary-400 line-clamp-1 mb-1">{{ $po->produk->nama }}</h3>
                            <p class="font-body text-body-sm text-on-surface-variant line-clamp-1 mb-2">{{ substr(strip_tags($po->produk->deskripsi ?? ''), 0, 80) }}...</p>

                            <!-- Order Details -->
                            <div class="flex items-center justify-between">
                                <div class="text-right">
                                    <p class="font-mono text-primary-600 dark:text-primary-400">
                                        Rp{{ number_format($po->total_harga, 0, ',', '.') }}
                                    </p>
                                    <p class="font-body text-caption text-on-surface-variant">
                                        {{ $po->jenis === 'sewa' ? 'Disewakan' : 'Dijual' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-neutral-500 dark:text-neutral-400 text-sm">
                                        {{ $po->tanggal_pesan ? \Carbon\Carbon::parse($po->tanggal_pesan)->relativeToNow() : 'Tidak diketahui' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($pesanan->count() === 0)
            <div class="col-span-full text-center py-24 animate-slide-up">
                <div class="inline-block mb-6">
                    <svg class="w-12 h-12 text-neutral-400 dark:text-neutral-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m0 0l-8 4 8-4M7 7h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2zm-1-1h2v2h-2V6zM7 3h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm-1-1h2v2h-2V4z"/>
                    </svg>
                </div>
                <h3 class="font-display text-lg font-medium text-neutral-600 dark:text-neutral-400 mb-2">Belum Ada Pesanan</h3>
                <p class="text-neutral-400 dark:text-neutral-400 text-sm">Anda belum memiliki pesanan sewa. Klik di bawah untuk memulai sewa produk impian Anda.</p>
                <a href="{{ route('pelanggan.catalog.index') }}" class="btn btn-primary mt-4">Browse Katalog</a>
            </div>
        @endif

        <!-- Pagination -->
        @if($pesanan->count() > 8)
            <nav class="my-8 animate-slide-up" aria-label="Paginasi">
                <div class="pagination">
                    @if($pesanan->hasPages())
                        @foreach($pesanan->links() as $link)
                            <a
                                href="{{ $link->url }}"
                                class="pagination-btn {{ $link->active ? 'active' : '' }}"
                            >
                                {{ $link->label }}
                            </a>
                        @endforeach
                    @endif
                </div>
            </nav>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Initialize Order-specific interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize filter functionality
    initOrderFilter();

    // Initialize search functionality
    initOrderSearch();

    // Initialize card tilt/magnetic effects
    initCardEffects();

    // Initialize scroll reveal
    MicroInteractions.scroll.reveal(document.querySelectorAll('.reveal'));

    // Initialize counters
    initCounters();
});

// Initialize order filter
function initOrderFilter() {
    const filterButtons = document.querySelectorAll('.filter-status');
    const cards = document.querySelectorAll('#ordersGrid .interactive-card');

    if (!filterButtons.length || !cards.length) return;

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const status = btnDataset.status;

            // Update active state
            filterButtons.forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline');
            btn.setAttribute('aria-pressed', 'true');

            // Filter cards
            cards.forEach(card => {
                const cardStatus = cardDataset.orderStatus;
                if (status === 'all' || cardStatus === status) {
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

// Initialize order search
function initOrderSearch() {
    const searchInput = document.getElementById('orderSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('#ordersGrid .interactive-card');

        cards.forEach(card => {
            const orderNumber = cardDataset.orderId.toLowerCase();
            const productName = card.querySelector('h3').textContent.toLowerCase();

            if (orderNumber.includes(searchTerm) || productName.includes(searchTerm)) {
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
}

// Initialize card effects
function initCardEffects() {
    // Initialize tilt effect for cards with data-tilt
    document.querySelectorAll('.interactive-card[data-tilt]').forEach(card => {
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
</script>
@endpush
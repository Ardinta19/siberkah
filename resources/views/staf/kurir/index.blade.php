@extends('layouts.staff')

@section('title', 'Kurir - SIBERKAH')

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

        <!-- Header -->
        <div class="mb-8 animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50">Manajemen Kurir</h2>
                    <p class="text-neutral-500 dark:text-neutral-400">Daftar kurir yang tersedia</p>
                </div>

                <!-- Statistik Kurir -->
                <div class="rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 p-4 sm:p-6 border border-primary-200/20">
                    <div class="grid grid-cols-2 gap-2">
                        @php $totalAktif = $kurir->where('status', 'aktif')->count(); @endphp
                        <div>
                            <p class="font-display text-sm text-primary-500 dark:text-primary-400">Aktif</p>
                            <p class="font-display text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $totalAktif }}</p>
                        </div>
                        @php $totalSedang = $kurir->where('status', 'sedang')->count(); @endphp
                        <div>
                            <p class="font-display text-sm text-primary-500 dark:text-primary-400">Sedang Antar</p>
                            <p class="font-display text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $totalSedang }}</p>
                        </div>
                        @php $totalOffline = $kurir->where('status', 'offline')->count(); @endphp
                        <div>
                            <p class="font-display text-sm text-primary-500 dark:text-primary-400">Offline</p>
                            <p class="font-display text-2xl font-bold text-error-600 dark:text-error-400">{{ $totalOffline }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kurir List -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6" id="kurirGrid">
            @foreach($kurir as $k)
                <article class="interactive-card group hover-lift cursor-pointer reveal"
                    role="listitem"
                    data-tilt="6">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-xl bg-primary-50/50 dark:bg-primary-950/50 p-4 sm:p-5">
                        <!-- Avatar Kurir -->
                        <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mx-auto mb-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-500">person</span>
                        </div>

                        <!-- Kurir Info -->
                        <h3 class="font-display font-medium text-primary-600 dark:text-primary-400 text-center">{{ $k->nama }}</h3>
                        <p class="text-primary-500 dark:text-primary-400 text-sm text-center mb-1">{{ $k->no_telepon }}</p>

                        <!-- Status -->
                        <div class="mt-3 p-3 rounded bg-{{ $k->status === 'aktif' ? 'primary' : ($k->status === 'sedang' ? 'warning' : 'error') }}-100/30 border border-{{ $k->status === 'aktif' ? 'primary' : ($k->status === 'sedang' ? 'warning' : 'error') }}-200/30">
                            <span class="material-symbols-outlined text-[12px]">{{ $k->status === 'aktif' ? 'circle' : ($k->status === 'sedang' ? 'send' : 'cancel') }}</span>
                            <span class="font-medium text-{{ $k->status === 'aktif' ? 'primary-800' : ($k->status === 'sedang' ? 'warning-800' : 'error-800') }}">{{ ucfirst($k->status) }}</span>
                        </div>

                        <!-- Action -->
                        <div class="mt-4 pt-4 border-t border-primary-100/20 dark:border-primary-900/20">
                            @if($k->status === 'offline')
                                <x-button wire:loading="isActivating" wire:submit="aktifkan"
                                        class="btn btn-primary w-full py-2 rounded-xl transition-all">
                                    <span>Aktifkan</span>
                                </x-button>
                            @else
                                <x-button wire:loading="isDeactivating" wire:submit="nonaktifkan"
                                        class="btn btn-danger w-full py-2 rounded-xl transition-all">
                                    <span>Nonaktifkan</span>
                                </x-button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($kurir->count() === 0)
            <div class="text-center py-24 animate-slide-up">
                <div class="inline-block mb-6">
                    <svg class="w-12 h-12 text-neutral-400 dark:text-neutral-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m0 0l-8 4 8-4M7 7h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2zm-1-1h2v2h-2V6zM7 3h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm-1-1h2v2h-2V4z"/>
                    </svg>
                </div>
                <h3 class="font-display text-lg font-medium text-neutral-600 dark:text-neutral-400 mb-2">Tidak Ada Kurir</h3>
                <p class="text-neutral-400 dark:text-neutral-400 text-sm">Belum ada kurir terdaftar. Tambah kurir baru untuk melanjutkan operasi.</p>
                <a href="#" class="btn btn-primary mt-4">Tambah Kurir Baru</a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Initialize Staf Kurir interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize counters if any
    initCounters();
});

// Initialize counters
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.counter) || 0;
                MicroInteractions.counter.animate(el, target, 2000, { decimals: 0 });
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
}
</scripts>
@endpush
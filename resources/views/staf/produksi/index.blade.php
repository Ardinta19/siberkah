@extends('layouts.staff')

@section('title', 'Produksi - SIBERKAH')

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
                    <h2 class="font-display text-2xl font-bold text-primary-900 sm:text-3xl dark:text-primary-50">Antrean Produksi</h2>
                    <p class="text-neutral-600 dark:text-neutral-400">Daftar produk menunggu proses produksi</p>
                </div>
            </div>
        </div>

        <!-- Statistik Cek Produksi -->
        <div class="mb-8 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 p-6 sm:p-8 border border-primary-200/20">
            <div class="grid grid-cols-3 gap-6">
                @php $totalMenunggu = $produk->where('status', 'menunggu')->count(); @endphp
                <div class="lifted">
                    <div class="text-center">
                        <div class="text-primary-500 text-sm font-medium dark:text-primary-400">Menunggu</div>
                        <p class="font-display text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $totalMenunggu }}</p>
                    </div>
                    <div class="mt-3">
                        <svg class="w-5 h-5 mx-auto text-primary-400 dark:text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                @php $totalProduksi = $produk->where('status', 'produksi')->count(); @endphp
                <div class="lifted">
                    <div class="text-center">
                        <div class="text-accent-500 text-sm font-medium dark:text-accent-400">Sedang Produksi</div>
                        <p class="font-display text-3xl font-bold text-accent-600 dark:text-accent-400">{{ $totalProduksi }}</p>
                    </div>
                    <div class="mt-3">
                        <svg class="w-5 h-5 mx-auto text-accent-400 dark:text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m-4 4h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8m-4 4v1a4 4 0 004 4m0-4V4a4 4 0 00-4-4H5a4 4 0 00-4 4v1"/>
                        </svg>
                    </div>
                </div>

                @php $totalSelesai = $produk->where('status', 'selesai')->count(); @endphp
                <div class="lifted">
                    <div class="text-center">
                        <div class="text-success-500 text-sm font-medium dark:text-success-400">Selesai</div>
                        <p class="font-display text-3xl font-bold text-success-600 dark:text-success-400">{{ $totalSelesai }}</p>
                    </div>
                    <div class="mt-3">
                        <svg class="w-5 h-5 mx-auto text-success-400 dark:text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6.25-5.858a3.535 3.535 0 115 5.158m5.158-5.158a3.535 3.535 0 01-5 5.158M2.105 5.5a3.535 3.535 0 115 5.158m5.158-5.158a3.535 3.535 0 01-5 5.158m1.414-9.999a3.535 3.535 0 000 5.006L19.99 19.99"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produk Queue -->
        <div class="space-y-6">
            @foreach($antrean as $item)
                <div class="rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 p-5 border border-primary-200/20 group hover:shadow-lg hover:transition-shadow duration-300">
                    <!-- Status Indicator -->
                    <div class="flex items-start gap-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-{{ $item->status === 'menunggu' ? 'primary-100/30' : ($item->status === 'produksi' ? 'accent-100/30' : 'success-100/30') }} border-{{ $item->status === 'menunggu' ? 'primary-200/30' : ($item->status === 'produksi' ? 'accent-200/30' : 'success-200/30') }} flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[16px]">{{ $item->status === 'menunggu' ? 'pending' : ($item->status === 'produksi' ? 'factory' : 'check_circle') }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-display font-medium text-primary-600 dark:text-primary-400">{{ $item->produk->nama }}</h3>
                            <p class="text-primary-500 dark:text-primary-400 text-sm">{{ $item->kategori->nama }}</p>
                        </div>
                    </div>

                    <!-- Progress & Info -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-neutral-500 dark:text-neutral-400 mb-1">Waktu Masuk</p>
                            <p class="font-medium text-primary-600 dark:text-primary-400">{{ \Carbon\Carbon::parse($item->created_at)->diffForHum() }}</p>
                        </div>
                        <div>
                            <p class="text-neutral-500 dark:text-neutral-400 mb-1">Prioritas</p>
                            <span class="px-2 py-1 rounded bg-primary-100/30 text-primary-600 text-xs font-medium">
                                Menunggu
                            </span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="mt-4 pt-4 border-t border-primary-100/20 dark:border-primary-900/20">
                        <x-button wire:loading="isProcessing" wire:submit="mulaiProduksi"
                            class="btn btn-primary w-full py-2 rounded-xl transition-all">
                            <span>Mulai Produksi</span>
                            @if($isProcessing)
                                <span class="sr-only">Memproses...</span>
                            @endif
                        </x-button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($antrean->count() === 0)
            <div class="text-center py-24 animate-slide-up">
                <div class="inline-block mb-6">
                    <svg class="w-12 h-12 text-neutral-400 dark:text-neutral-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m0 0l-8 4 8-4M7 7h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2zm-1-1h2v2h-2V6zM7 3h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm-1-1h2v2h-2V4z"/>
                    </svg>
                </div>
                <h3 class="font-display text-lg font-medium text-neutral-600 dark:text-neutral-400 mb-2">Tidak Ada Antrean</h3>
                <p class="text-neutral-400 dark:text-neutral-400 text-sm">Tidak ada produk menunggu produksi. Semua produk sedang dalam proses atau sudah selesai.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Initialize Staf Produksi interactions
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
</script>
@endpush
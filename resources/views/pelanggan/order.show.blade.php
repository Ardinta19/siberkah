@extends('layouts.customer')

@section('title', '{{ $pesanan->produk->nama }} - SIBERKAH')

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

        <!-- Breadcrumbs -->
        <nav class="mb-8 animate-slide-up" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                <li>
                    <a href="{{ route('home') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Beranda</a>
                </li>
                <li>
                    <span class="text-neutral-400">{{ $pesanan->produk->kategori->nama ?? 'Katalog' }}</span>
                </li>
                <li>
                    <span class="font-display text-primary-600">{{ $pesanan->produk->nama }}</span>
                </li>
            </ol>
        </nav>

        <!-- Order Header -->
        <div class="mb-12 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 overflow-hidden">
            <div class="px-6 py-8">
                <!-- Order Status -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-primary-100/30 border border-primary-200 text-primary-800 text-xs font-medium px-2.5 py-1 rounded-full">
                            <span class="material-symbols-outlined text-[10px] text-primary-500">circle</span>
                            {{ ucfirst($pesanan->status) }}
                        </span>
                    </div>
                    <p class="text-primary-600 font-semibold text-2xl">Rp {{ number_format($pesanan->total_harga, 0, ',', '.') }}</p>
                </div>

                <!-- Order Details -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Detail Pesanan</h3>
                        <p class="text-primary-600 dark:text-primary-400 text-sm">
                            <span class="font-medium">Produk:</span> {{ $pesanan->produk->nama }}
                        </p>
                        <p class="text-primary-600 dark:text-primary-400 text-sm">
                            <span class="font-medium">Jenis:</span> {{ $pesanan->jenis === 'sewa' ? 'Disewakan' : 'Dijual' }}
                        </p>
                        @if($pesanan->jenis === 'sewa')
                            <p class="text-primary-600 dark:text-primary-400 text-sm">
                                <span class="font-medium">Durasi Sewa:</span> {{ optional($pesanan->durasi_sewa) ?? '1 Bulan' }} {{ optional($pesanan->satuan_durasi) ?? 'Bulan' }}
                            </p>
                        @endif
                        <p class="text-primary-600 dark:text-primary-400 text-sm">
                            <span class="font-medium">Tanggal Pesan:</span> {{ \Carbon\Carbon::parse($pesanan->tanggal_pesan)->format('d F Y') }}
                        </p>
                    </div>

                    <div>
                        <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Alamat Pengiriman</h3>
                        <p class="text-primary-600 dark:text-primary-400 text-sm">
                            {{ $pesanan->alamat_lengkap ?? 'Belum ada alamat' }}
                        </p>
                        <p class="text-primary-600 dark:text-primary-400 text-sm">
                            <span class="font-medium">No. Telepon:</span> {{ $pesanan->no_telepon ?? 'Belum terdaftar' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="mb-12">
            <h2 class="font-display text-2xl font-bold tracking-tight text-primary-900 dark:text-primary-50 mb-4">Item Pesanan</h2>
            <div class="overflow-x-auto rounded-2xl bg-primary-50/50 dark:bg-primary-950/50">
                <table class="table w-full">
                    <thead>
                        <tr class="text-neutral-600 dark:text-neutral-400">
                            <th class="px-4 py-3 text-left">Produk</th>
                            <th class="px-4 py-3 text-center">Varian</th>
                            <th class="px-4 py-3 text-right">Harga</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pesanan->items as $item)
                            <tr class="border-b border-primary-100/20 dark:border-primary-900/20 hover:bg-primary-50/20 transition-colors-150">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="font-medium text-neutral-900 dark:text-neutral-100">{{ $item->produk_nama }}</p>
                                    <p class="text-xs text-primary-500 dark:text-primary-400">{{ $item->produk_kategori }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <p class="font-body text-body-sm text-on-surface-variant">{{ $item->varian_nama ?? '--' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <p class="font-mono text-primary-600 dark:text-primary-400">Rp {{ number_format($item->harga, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <p class="font-body text-body-sm text-on-surface-variant">{{ $item->qty }} {{ $item->satuan }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="bg-{{ $item->status_varian === 'tersedia' ? 'primary' : ($item->status_varian === 'habis' ? 'error' : 'outline') }}-100/30 border border-{{ $item->status_varian === 'tersedia' ? 'primary' : ($item->status_varian === 'habis' ? 'error' : 'outline') }}-200/30 text-{{ $item->status_varian === 'tersedia' ? 'primary-800' : ($item->status_varian === 'habis' ? 'error-800' : 'on-surface-variant') }}-text-xs font-medium px-2.5 py-1 rounded-full">
                                        @if($item->status_varian === 'tersedia')
                                            Tersedia
                                        @elseif($item->status_varian === 'habis')
                                            Habis
                                        @else
                                            {{ ucfirst($item->status_varian) }}
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="mb-12 rounded-xl bg-primary-50/50 dark:bg-primary-950/50 p-6">
            <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-4">Timeline Pesanan</h3>
            <div class="space-y-3">
                @foreach($pesanan->timeline as $step)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-100/30 border border-primary-200/30 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary-400 text-sm">{{ $step->icon }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-display text-sm font-medium text-primary-600 dark:text-primary-400">{{ $step->title }}</p>
                            <p class="text-primary-500 dark:text-primary-400 text-xs">{{ $step->description }}</p>
                        </div>
                        <svg class="w-4 h-4 text-primary-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l13 13M5 8l13-13M17 5l13 13M21 9l-5 5L8 21"/>
                        </svg>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Buttons -->
        @if($pesanan->status === 'pending')
            <div class="pt-6 border-t border-primary-100/20 dark:border-primary-900/20">
                <x-button wire:loading="isLoading" wire:submit="batal"
                        class="btn btn-danger w-full py-3 rounded-xl transition-all">
                    <span>Batal Pesanan</span>
                    @if($isLoading)
                        <span class="sr-only">Memproses...</span>
                    @endif
                </x-button>
            </div>
        @elseif($pesanan->status === 'confirmed')
            <div class="pt-6 border-t border-primary-100/20 dark:border-primary-900/20">
                <x-button wire:loading="isLoading" wire:submit="proses"
                        class="btn btn-primary w-full py-3 rounded-xl transition-all">
                    <span>Proses Pesanan</span>
                    @if($isLoading)
                        <span class="sr-only">Memproses...</span>
                    @endif
                </x-button>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Initialize Order Show interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize timeline animation
    initTimeline();

    // Initialize counters if any
    initCounters();
});

// Initialize timeline
function initTimeline() {
    const steps = document.querySelectorAll('.timeline-step');
    if (!steps.length) return;

    // Add fade-in animation based on visibility
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateX(0)';
            }
        });
    }, { threshold: 0.3 });

    steps.forEach(step => {
        step.style.opacity = '0';
        step.style.transform = 'translateX(-20px)';
        step.style.transition = 'opacity 400ms ease-out, transform 400ms ease-out';
        observer.observe(step);
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
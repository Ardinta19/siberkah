@extends('layouts.guest')

@section('title', '{{ $produk->nama }} - SIBERKAH')

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
                    <span class="text-neutral-400">{{ $produk->kategori->nama ?? 'Katalog' }}</span>
                </li>
                <li>
                    <span class="font-display text-primary-600">{{ $produk->nama }}</span>
                </li>
            </ol>
        </nav>

        <!-- Product Gallery -->
        <div class="mb-12 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 overflow-hidden">
            <div class="image-gallery">
                <!-- Main Image -->
                <div class="image-gallery-main">
                    <img
                        id="mainImage"
                        src="{{ asset('storage/' . $produk->gambar_utama) }}"
                        alt="{{ $produk->nama }}"
                        class="w-full h-[450px] object-cover rounded-xl transition-opacity duration-300 ease-out loading=lazy"
                        loading="lazy"
                    >
                </div>

                <!-- Thumbnails -->
                <div class="image-gallery-thumbs" id="productThumbs">
                    @if($produk->gambar_lain)
                        @foreach($produk->gambar_lain as $index => $gambar)
                            <div class="image-gallery-thumb {{ $index === 0 ? 'active' : '' }}"
                                 @click="mainImage.src = '/storage/{{ $gambar }}'"
                                 aria-label="Gambar {{ $index + 1 }}"
                                 class="cursor-pointer transition-opacity duration-200"
                                 style="opacity: {{ $index === 0 ? 1 : 0.6 }};"
                                 @mouseenter="this.style.opacity='1'"
                                 @mouseleave="this.style.opacity='{{ $index === 0 ? 1 : 0.6 }}'">
                                <img src="{{ asset('storage/' . $gambar) }}" alt="" class="w-full h-24 object-cover rounded-md border border-primary-200 dark:border-primary-700 transition-all duration-200 hover:scale-110">
                            </div>
                        @endforeach
                    @endif
                    @if($produk->varians)
                        @foreach($produk->varians as $index => $varian)
                            @if($varian->gambar)
                                <div class="image-gallery-thumb {{ $index === 0 ? 'active' : '' }}"
                                     @click="mainImage.src = '/storage/{{ $varian->gambar }}'"
                                     aria-label="Gambar varian {{ $varian->nama }}"
                                     class="cursor-pointer transition-opacity duration-200"
                                     style="opacity: {{ $index === 0 ? 1 : 0.6 }};"
                                     @mouseenter="this.style.opacity='1'"
                                     @mouseleave="this.style.opacity='{{ $index === 0 ? 1 : 0.6 }}'">
                                    <img src="{{ asset('storage/' . $varian->gambar) }}" alt="" class="w-full h-24 object-cover rounded-md border border-primary-200 dark:border-primary-700 transition-all duration-200 hover:scale-110">
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="grid md:grid-cols-2 gap-8">

            <!-- Detail Section -->
            <div>
                <h1 class="font-display text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight text-primary-900 dark:text-primary-50 mb-4">{{ $produk->nama }}</h1>

                <p class="text-neutral-600 dark:text-neutral-400 mb-6 leading-relaxed">{{ $produk->deskripsi }}</p>

                <!-- Harga & Status -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="status-badge status-available" style="animation-delay: 100ms;">
                            <span class="material-symbols-outlined text-[10px] text-primary-500">circle</span>
                            Tersedia
                        </span>
                        <span class="text-primary-600 font-semibold">Rp {{ number_format($produk->harga_dasar ?? 0, 0, ',', '.') }}</span>
                    </div>

                    @if($produk->jenis === 'sewa')
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            <span class="font-medium">Jenis:</span> Disewakan
                        </p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            <span class="font-medium">Kapasitas:</span> {{ optional($produk->varianProduk->first()->nama) ?? 'Variasi'}
                        </p>
                    @elseif($produk->jenis === 'jual')
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            <span class="font-medium">Jenis:</span> Dijual
                        </p>
                    @endif
                </div>

                <!-- Variants -->
                @if($produk->varianProduk->count() > 0)
                    <div class="mb-8">
                        <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Varian Tersedia</h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($produk->varianProduk->where('aktif', true) as $v)
                                <button class="btn btn-sm btn-outline w-full justify-center py-2 bg-primary-50/30 border border-primary-200/30 text-primary-700 rounded-lg transition-colors"
                                        hx-get="/katalog/{{ $produk->slug }}?varian={{ $v->id }}"
                                        hx-target="#katalogGrid"
                                        hx-swap="outerHTML"
                                >
                                    {{ $v->nama }} - Rp {{ number_format($v->harga, 0, ',', '.') }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Aksi -->
                <div class="pt-6 border-t border-primary-100/20 dark:border-primary-900/20">
                    <x-button wire:loading="isLoading" wire:submit="sewa"
                            class="btn btn-primary w-full py-3 rounded-xl transition-all">
                        <span>Sewa Sekarang</span>
                        @if($isLoading)
                            <span class="sr-only">Memproses...</span>
                        @endif
                    </x-button>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-6">
                <!-- Fitur Utama -->
                <div>
                    <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Fitur Utama</h3>
                    <ul class="space-y-2 text-primary-600 dark:text-primary-400 text-sm">
                        @foreach($produk->fitur ?? [] as $f)
                            <li class="flex items-start">
                                <span class="material-symbols-outlined flex-shrink-0 text-primary-400 text-sm">star</span>
                                <span>{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Berat & Dimensi -->
                <div>
                    <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Spesifikasi</h3>
                    <p class="text-primary-600 dark:text-primary-400 text-sm">
                        <span class="font-medium">Berat:</span> {{ optional($produk->berat) ?? 'N/A' }} kg
                    </p>
                    <p class="text-primary-600 dark:text-primary-400 text-sm">
                        <span class="font-medium">Dimensi:</span> {{ optional($produk->dimensi) ?? 'N/A' }}
                    </p>
                </div>

                <!-- Konfirmasi -->
                <div>
                    <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Konfirmasi</h3>
                    <p class="text-primary-600 dark:text-primary-400 text-sm">
                        Sewa ini memerlukan konfirmasi via WhatsApp sebelum diproses.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Gallery Thumbnail Hover Script -->
<script>
    // Thumbnail hover effect
    document.addEventListener('DOMContentLoaded', function() {
        const thumbs = document.querySelectorAll('#productThumbs .image-gallery-thumb');
        const mainImage = document.getElementById('mainImage');

        thumbs.forEach(thumb => {
            thumb.addEventListener('mouseenter', function() {
                thumbs.forEach(t => t.style.opacity = '0.6');
                this.style.opacity = '1';
            });

            thumb.addEventListener('mouseleave', function() {
                thumbs.forEach(t => t.style.opacity = '{{ $index === 0 ? 1 : 0.6 }}');
            });
        });

        // Main image fade in on load
        mainImage.style.opacity = '0';
        mainImage.addEventListener('load', function() {
            mainImage.style.transition = 'opacity 300ms ease-out';
            mainImage.style.opacity = '1';
        });
    });

    // Initialize variant selection
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight active variant button
        const variantButtons = document.querySelectorAll('.varian-btn.active');
        if (variantButtons.length > 0) {
            variantButtons.forEach(btn => {
                btn.style.borderColor = 'var(--color-primary-500)';
                btn.style.color = 'var(--color-primary-500)';
            });
        }
    });
</script>
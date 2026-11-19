@extends('layouts.admin')

@section('title', "SIBERKAH - {{ $produk->nama }}")

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Breadcrumbs -->
        <nav class="mb-8 animate-slide-up" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                <li><a href="{{ route('admin.katalog.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Katalog</a></li>
                <li>/</li>
                <li class="font-display text-primary-600">{{ $produk->nama }}</li>
            </ol>
        </nav>

        <!-- Header with action buttons -->
        <div class="flex items-center justify-between mb-8">
            <h2 class="font-display text-2xl font-bold text-primary-900 sm:text-3xl dark:text-primary-50">{{ $produk->nama }}</h2>
            <div class="flex gap-2">
                <x-button variant="primary" size="sm" href="{{ route('admin.katalog.edit', $produk->id) }}">
                    Edit
                </x-button>
                <form method="POST" action="{{ route('admin.katalog.archive', $produk->id) }}" onsubmit="return confirm('Arsipkan produk ini?');">
                    @csrf
                    <x-button variant="danger" size="sm" type="submit">
                        Arsipkan
                    </x-button>
                </form>
                @if($produk->aktif)
                    <form method="POST" action="{{ route('admin.katalog.archive', $produk->id) }}" onsubmit="return confirm('Ubah status menjadi draft?');">
                        @csrf
                        <x-button variant="secondary" size="sm" type="submit">
                            Draft
                        </x-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.katalog.publish', $produk->id) }}" onsubmit="return confirm('Publikasikan produk ini?');">
                        @csrf
                        <x-button variant="success" size="sm" type="submit">
                            Terbitkan
                        </x-button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Image gallery -->
        <div class="mb-12 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                <!-- Main Image -->
                <div class="relative">
                    <img src="{{ asset('storage/' . $produk->foto_utama) }}" alt="{!! $produk->nama !!}" class="w-full h-64 object-cover rounded-lg loading=lazy" loading="lazy">
                </div>

                <!-- Thumbnail Gallery -->
                <div class="flex flex-col gap-2 overflow-y-auto max-h-96">
                    @if($produk->gambar_lain)
                        @foreach($produk->gambar_lain as $index => $gambar)
                            <img src="{{ asset('storage/' . $gambar) }}" alt="{!! $produk->nama !!}" class="w-full h-24 object-cover rounded-md border border-primary-200 dark:border-primary-700 transition-all duration-200 hover:scale-110 cursor-pointer"
                                 @mouseenter="this.style.opacity='1'"
                                 @mouseleave="this.style.opacity='0.6'"
                                 :key="index">
                        @endforeach
                    @endif
                    @if($produk->varians)
                        @foreach($produk->varians as $index => $varian)
                            @if($varian->gambar)
                                <img src="{{ asset('storage/' . $varian->gambar) }}" alt="{!! $varian->nama !!}" class="w-full h-24 object-cover rounded-md border border-primary-200 dark:border-primary-700 transition-all duration-200 hover:scale-110 cursor-pointer"
                                     @mouseenter="this.style.opacity='1'"
                                     @mouseleave="this.style.opacity='0.6'"
                                     :key="varian->id">
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Product meta -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">

            <!-- Description -->
            <div>
                <p class="text-neutral-600 dark:text-neutral-400 mb-4">{{ $produk->deskripsi }}</p>

                <!-- Harga -->
                <div class="mb-4">
                    <span class="font-medium text-neutral-600 dark:text-neutral-300">Harga Sewa:</span>
                    <span class="text-primary-600 font-semibold">Rp {{ number_format($produk->harga_sewa, 0, ',', '.') }}</span>
                </div>

                @if($produk->harga_jual)
                    <div class="mb-4">
                        <span class="font-medium text-neutral-600 dark:text-neutral-300">Harga Jual:</span>
                        <span class="line-through text-neutral-500">Rp {{ number_format($produk->harga_jual, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if($produk->opsi_produk->isNotEmpty())
                    <div class="mb-4">
                        <p class="font-medium text-neutral-600 dark:text-neutral-300">Opsi Produk:</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($produk->opsi_produk as $opsi)
                                <x-badge variant="primary" class="text-xs">{{ $opsi->nama }}</x-badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($produk->varians->isNotEmpty())
                    <div class="mb-4">
                        <p class="font-medium text-neutral-600 dark:text-neutral-300">Varian & Stok:</p>
                        <table class="w-full table-auto mt-2">
                            <thead>
                                <tr class="text-left text-neutral-600 dark:text-neutral-400">
                                    <th class="px-2 py-1 font-display font-medium text-primary-600 dark:text-primary-400">Nama</th>
                                    <th class="px-2 py-1 font-display font-medium text-primary-600 dark:text-primary-400">Stok</th>
                                    <th class="px-2 py-1 font-display font-medium text-primary-600 dark:text-primary-400">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($produk->varians as $varian)
                                    <tr class="border-b border-primary-100/20 dark:border-primary-900/20 hover:bg-primary-50 dark:hover:bg-primary-950/20 transition-colors">
                                        <td class="px-2 py-1">{{ $varian->nama }}</td>
                                        <td class="px-2 py-1">{{ $varian->stok ?? 'N/A' }}</td>
                                        <td class="px-2 py-1">Rp {{ number_format($varian->harga ?? $produk->harga_sewa, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Status & Kategori -->
            <div>
                <p class="font-medium text-neutral-600 dark:text-neutral-300 mb-2">Status:</p>
                <span class="status-badge status-{{ $produk->aktif ? 'available' : 'reserving' }}">
                    {{ $produk->aktif ? 'Aktif' : 'Draft' }}
                </span>
                <p class="mt-4 font-medium text-neutral-600 dark:text-neutral-300">Kategori:</p>
                <p>{{ $produk->kategori->nama ?? 'Tidak ada' }}</p>
            </div>
        </div>

    </div>
</div>
@endsection
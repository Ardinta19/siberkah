@extends('layouts.admin')

@section('title', 'SIBERKAH - Kelola Katalog')

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Header -->
        <div class="mb-8 animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-2xl font-bold text-primary-900 sm:text-3xl dark:text-primary-50">Kelola Produk</h2>
                    <p class="text-neutral-600 dark:text-neutral-400">Kelola produk sewa, akrilik, dan dekorasi</p>
                </div>

                <!-- Tombol Tambah Produk -->
                <div>
                    <x-button
                        variant="primary"
                        size="sm"
                        @click="bukaModalProduk"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            <span class="ml-2">Tambah Produk</span>
                        </svg>
                    </x-button>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 animate-slide-up" style="transition-delay: 100ms;">
            @php($total = count($produk))
            @php($tersedia = $produk->where('status', 'tersedia')->count())
            @php($reservasi = $produk->where('status', 'reservasi')->count())
            @php($habis = $produk->where('status', 'habis')->count())

            <div class="stat-card status-available lifted">
                <div class="stat-value">{{ $tersedia }}</div>
                <div class="stat-label">Tersedia</div>
            </div>

            <div class="stat-card status-reserving lifted">
                <div class="stat-value">{{ $reservasi }}</div>
                <div class="stat-label">Reservasi</div>
            </div>

            <div class="stat-card status-out-of-stock lifted">
                <div class="stat-value">{{ $habis }}</div>
                <div class="stat-label">Habis</div>
            </div>

            <div class="stat-card status-maintenance lifted">
                <div class="stat-value">{{ $total - $tersedia - $reservasi - $habis }}</div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <!-- Tabel Produk -->
        <div class="overflow-x-auto rounded-2xl bg-white dark:bg-neutral-900 shadow-lg overflow-hidden">
            <table class="table w-full">
                <thead>
                    <tr class="border-b border-primary-100/20 dark:border-primary-900/20">
                        <th class="px-4 py-3 text-left font-display font-medium text-primary-600 dark:text-primary-400">Nama Produk</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-primary-600 dark:text-primary-400">Kategori</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-primary-600 dark:text-primary-400">Harga Sewa</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-primary-600 dark:text-primary-400">Status</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-primary-600 dark:text-primary-400">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produk as $p)
                    <tr class="border-b border-primary-100/20 dark:border-primary-900/20 hover:bg-primary-50 dark:hover:bg-primary-950/20 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p class="font-medium text-primary-900 dark:text-primary-50">{{ $p->nama }}</p>
                            <p class="text-neutral-500 dark:text-neutral-400 text-sm">{{ $p->kategori->nama ?? 'Tanpa kategori' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $p->kategori->nama ?? 'Tanpa kategori' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-primary-600 font-medium">Rp {{ number_format($p->harga_sewa, 0, ',', '.') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="status-badge status-{{ $p->status == 'tersedia' ? 'available' : $p->status == 'reservasi' ? 'reserving' : $p->status == 'habis' ? 'out-of-stock' : 'maintenance' }}">
                                {{ $p->status == 'tersedia' ? 'Tersedia' : $p->status == 'reservasi' ? 'Reservasi' : $p->status == 'habis' ? 'Habis' : 'Maintenance' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <!-- Aksi: Edit, Detail, Hapus -->
                            <x-button
                                variant="ghost"
                                size="sm"
                                class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.2l-1.95-1.95m13.05 13.05L15.55 15.55M11 15.25V5.75m8 12a8 8 0 11-16 0 8 8 0 0116 0z"/>
                                </svg>
                                Edit
                            </x-button>

                            <x-button
                                variant="ghost"
                                size="sm"
                                class="ml-2 text-danger-600 hover:text-danger-400 transition-colors"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L6 6M6 6l12 12"/>
                                </svg>
                                Hapus
                            </x-button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($produk->count() > 8)
            <nav class="my-8 animate-slide-up" aria-label="Paginasi">
                <div class="pagination">
                    @if($produk->hasPages())
                        @foreach($produk->links() as $link)
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
@endsection
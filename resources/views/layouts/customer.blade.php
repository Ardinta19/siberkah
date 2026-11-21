<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIBERKAH - Pelanggan')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>
<body data-workspace class="min-h-screen flex flex-col bg-neutral-50 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-50">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    <span id="bk-scroll-marker" aria-hidden="true"></span>
    <header class="sticky top-0 z-[var(--z-sticky)] bg-white/95 backdrop-blur-sm border-b border-neutral-200 dark:bg-neutral-900/95 dark:border-neutral-800 transition-shadow-200" id="main-header">
        @include('partials.expand-pill')
                <div id="bk-nav-controls" class="container-siberkah bk-full-nav">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('catalog.index') }}" class="flex items-center gap-2 text-primary-600 hover:text-primary-700 transition-colors-150 focus-ring rounded-lg px-2 py-1 -ml-2" aria-label="SIBERKAH - Beranda">
                    <svg class="w-8 h-8" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect width="48" height="48" rx="12" fill="currentColor"/>
                        <path d="M12 24L20 32L36 16" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="bk-wordmark font-bold text-xl hidden sm:block">SIBERKAH</span>
                </a>

                <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi pelanggan">
                    <a href="{{ route('catalog.index') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Katalog
                    </a>
                    <a href="{{ route('pesanan.index') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Pesanan Saya
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    @include('partials.product-launcher')
                    @include('partials.mobile-menu-toggle')
                    <div class="hidden sm:flex items-center gap-2 px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-xl">
                        <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ auth('customer')->user()->nama ?? 'Pelanggan' }}</span>
                    </div>

                    <div class="relative" data-account>
                        <button type="button" class="p-2 rounded-lg text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition-colors-150" aria-label="Menu pengguna" aria-expanded="false" aria-controls="account-links" data-account-toggle>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>

                        <div id="account-links" hidden class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-neutral-200 py-1 z-[var(--z-dropdown)] dark:bg-neutral-900 dark:border-neutral-700" >
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800" >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profil
                            </a>
                            <a href="{{ route('pesanan.index') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800" >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m12 0l-4 4m4-4l4 4m-7-4h10a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m0 0l4 4m-4-4l-4 4"/>
                                </svg>
                                Pesanan Saya
                            </a>
                            <hr class="my-1 border-neutral-200 dark:border-neutral-700">
                            <form action="{{ route('otp.logout') }}" method="POST" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-900/20 dark:text-danger-400" >
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('partials.mobile-radial')
    </header>

    <main id="main-content" class="flex-1" role="main">
        @yield('content')
    </main>

    <footer class="bg-neutral-100 border-t border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800" role="contentinfo">
        <div class="container-siberkah py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-neutral-500 dark:text-neutral-400">
                <p>&copy; {{ date('Y') }} SIBERKAH - Berkah Kreatif.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('syarat-sewa') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150">Syarat Sewa</a>
                    <a href="{{ route('privasi') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150">Privasi</a>
                    <a href="{{ route('kontak') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150">Kontak</a>
                </div>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="fixed bottom-6 right-6 z-[var(--z-toast)] flex flex-col gap-2" role="region" aria-label="Notifikasi" aria-live="polite"></div>

    @yield('scripts')
@include('partials.storefront-palette')
    @include('partials.mobile-dock')
</body>
</html>
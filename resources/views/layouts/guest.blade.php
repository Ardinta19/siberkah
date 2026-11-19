<!DOCTYPE html>
<html lang="id" class="{{ config('app.debug') ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIBERKAH - Sewa Papan Berkah Kreatif')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>
<body class="min-h-screen flex flex-col bg-neutral-50 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-50">
    <!-- Skip link for accessibility -->
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    <!-- Header -->
    <header class="sticky top-0 z-[var(--z-sticky)] bg-white/95 backdrop-blur-sm border-b border-neutral-200 dark:bg-neutral-900/95 dark:border-neutral-800 transition-shadow-200" id="main-header">
        <div class="container-siberkah">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary-600 hover:text-primary-700 transition-colors-150 focus-ring rounded-lg px-2 py-1 -ml-2" aria-label="SIBERKAH - Beranda">
                    <svg class="w-8 h-8" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect width="48" height="48" rx="12" fill="currentColor"/>
                        <path d="M12 24L20 32L36 16" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="font-bold text-xl hidden sm:block">SIBERKAH</span>
                </a>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi utama">
                    <a href="{{ route('catalog.index') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Katalog
                    </a>
                    <a href="{{ route('syarat-sewa') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Syarat Sewa
                    </a>
                    <a href="{{ route('privasi') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Privasi
                    </a>
                    <a href="{{ route('kontak') }}" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Kontak
                    </a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    @auth('customer')
                        <a href="{{ route('pesanan.index') }}" class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m12 0l-4 4m4-4l4 4m-7-4h10a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m0 0l4 4m-4-4l-4 4"/>
                            </svg>
                            Pesanan
                        </a>
                        <form action="{{ route('otp.logout') }}" method="POST" class="hidden sm:inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-danger-600 hover:bg-danger-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-danger-400 dark:hover:bg-danger-900/20">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('otp.form') }}" class="btn btn-primary btn-sm">
                            Masuk / Daftar
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <button type="button" class="md:hidden p-2 rounded-lg text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition-colors-150" aria-label="Buka menu" aria-expanded="false" aria-controls="mobile-menu" @click="open = !open" x-data="{ open: false }">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" class="md:hidden overflow-hidden bg-white border-t border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800" id="mobile-menu" role="navigation" aria-label="Menu mobile">
                <div class="py-4 space-y-1 px-4">
                    <a href="{{ route('catalog.index') }}" class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Katalog
                    </a>
                    <a href="{{ route('syarat-sewa') }}" class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Syarat Sewa
                    </a>
                    <a href="{{ route('privasi') }}" class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Privasi
                    </a>
                    <a href="{{ route('kontak') }}" class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                        Kontak
                    </a>
                    @auth('customer')
                        <a href="{{ route('pesanan.index') }}" class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20">
                            Pesanan Saya
                        </a>
                        <form action="{{ route('otp.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-base font-medium text-neutral-600 hover:text-danger-600 hover:bg-danger-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-danger-400 dark:hover:bg-danger-900/20">
                                Keluar
                            </button>
                        </form>
                    @else
                        <a href="{{ route('otp.form') }}" class="block px-3 py-2 text-base font-medium text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-primary-400 dark:hover:bg-primary-900/20">
                            Masuk / Daftar
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main id="main-content" class="flex-1" role="main">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-neutral-100 border-t border-neutral-200 dark:bg-neutral-900 dark:border-neutral-800" role="contentinfo">
        <div class="container-siberkah py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="space-y-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary-600 dark:text-primary-400" aria-label="SIBERKAH - Beranda">
                        <svg class="w-8 h-8" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                            <rect width="48" height="48" rx="12" fill="currentColor"/>
                            <path d="M12 24L20 32L36 16" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="font-bold text-xl">SIBERKAH</span>
                    </a>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed">
                        Penyewaan papan pengucapan, akrilik, dan dekorasi acara dengan kualitas terbaik dan harga terjangkau.
                    </p>
                    <div class="flex gap-4">
                        <a href="https://instagram.com/berkahkreatif_" target="_blank" rel="noopener noreferrer" class="text-neutral-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150" aria-label="Instagram @berkahkreatif_">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.073 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.441s.645 1.441 1.441 1.441c.795 0 1.439-.645 1.439-1.441s-.644-1.441-1.439-1.441z"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/628xxxxxxxxxx" target="_blank" rel="noopener noreferrer" class="text-neutral-400 hover:text-success-600 dark:hover:text-success-400 transition-colors-150" aria-label="WhatsApp">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.52 3.48A11.914 11.914 0 0012 1.44 11.9 11.9 0 003.48 3.48C1.5 4.67.43 6.55.38 8.49c-.04 1.55.5 3.36 1.71 4.62l-1.6 5.76c-.26.94.16 1.86 1.06 2.1.88.24 1.74-.08 2.09-.64l4.48-3.51c1.5 1.72 3.9 2.94 6.52 2.94 1.58 0 3.14-.31 4.59-.9l4.07 3.24c.35.28.77.17 1.09-.19.9-.9.97-2.32.47-3.31l-1.42-4.55c1.03-.63 1.94-1.52 2.34-2.65A11.92 11.92 0 0020.52 3.48z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Links -->
                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Layanan</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('catalog.index') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Sewa Papan Ucapan</a></li>
                        <li><a href="{{ route('catalog.index') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Sewa Akrilik</a></li>
                        <li><a href="{{ route('catalog.index') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Dekorasi Acara</a></li>
                        <li><a href="{{ route('catalog.index') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Custom Design</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Bantuan</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('syarat-sewa') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Syarat Sewa</a></li>
                        <li><a href="{{ route('privasi') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('kontak') }}" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">Kontak Kami</a></li>
                        <li><a href="#" class="text-sm text-neutral-600 hover:text-primary-600 dark:text-neutral-400 dark:hover:text-primary-400 transition-colors-150">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100 mb-3">Kontak</h3>
                    <ul class="space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Jl. Contoh No. 123, Kota</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>hello@berkahkreatif.com</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>+62 8xx-xxxx-xxxx</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-neutral-200 dark:border-neutral-700 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    &copy; {{ date('Y') }} SIBERKAH - Berkah Kreatif. Hak cipta dilindungi.
                </p>
                <div class="flex items-center gap-4 text-sm text-neutral-500 dark:text-neutral-400">
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150">Syarat & Ketentuan</a>
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors-150">Kebijakan Cookie</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[var(--z-toast)] flex flex-col gap-2" role="region" aria-label="Notifikasi" aria-live="polite"></div>

    @yield('scripts')
</body>
</html>
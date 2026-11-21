<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIBERKAH - Sewa Papan Berkah Kreatif')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>
<body data-storefront class="min-h-screen flex flex-col bg-[#fffdf9] text-neutral-900">
    <!-- Skip link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only">Lewati ke konten utama</a>

    <span id="bk-scroll-marker" aria-hidden="true"></span>
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-[#fffdf9] backdrop-blur-sm border-b border-neutral-200 transition-shadow-200" id="main-header">
        <div class="container-siberkah">
            @include('partials.expand-pill')
            <div id="bk-nav-controls" class="bk-full-nav flex items-center gap-2 h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary-600 hover:text-primary-700 transition-colors-150 focus-ring rounded-lg px-2 py-1 -ml-2" aria-label="SIBERKAH - Beranda">
                    <img src="{{ asset('images/berkah-kreatif.webp') }}" width="44" height="44" alt="" class="size-11 object-contain">
                    <span class="bk-wordmark font-bold text-xl block">SIBERKAH</span>
                </a>

                <!-- Navigation -->
                <nav class="hidden md:flex ml-auto items-center gap-1" aria-label="Navigasi utama">
                    <a href="{{ route('catalog.index') }}" @if($current = \App\Support\StorefrontNavigation::current('catalog.index')) aria-current="{{ $current }}" @endif class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Katalog
                    </a>
                    <a href="{{ route('syarat-sewa') }}" @if($current = \App\Support\StorefrontNavigation::current('syarat-sewa')) aria-current="{{ $current }}" @endif class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Syarat Sewa
                    </a>
                    <a href="{{ route('privasi') }}" @if($current = \App\Support\StorefrontNavigation::current('privasi')) aria-current="{{ $current }}" @endif class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Privasi
                    </a>
                    <a href="{{ route('kontak') }}" @if($current = \App\Support\StorefrontNavigation::current('kontak')) aria-current="{{ $current }}" @endif class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Kontak
                    </a>
                </nav>

                <!-- Actions -->
                <div class="ml-auto flex items-center gap-2">

                    @auth('customer')
                        <a href="{{ route('pesanan.index') }}" class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m12 0l-4 4m4-4l4 4m-7-4h10a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v7m0 0l4 4m-4-4l-4 4"/>
                            </svg>
                            Pesanan
                        </a>
                        @if(Route::has('otp.logout'))
                        <form action="{{ route('otp.logout') }}" method="POST" class="hidden sm:inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-neutral-600 hover:text-danger-600 hover:bg-danger-50 rounded-xl transition-colors-150">
                                Keluar
                            </button>
                        </form>
                        @endif
                    @else
                        <a href="{{ route('otp.form') }}" class="hidden sm:inline-flex btn btn-primary btn-sm">
                            Masuk / Daftar
                        </a>
                    @endauth
                </div>

                @include('partials.product-launcher')
                <!-- Mobile menu button -->
            @include('partials.mobile-menu-toggle')
                    <a class="bk-cart" href="{{ route('cart.index') }}" aria-label="Keranjang, {{ array_sum(session('cart', [])) }} item"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 3h3l3 13h11l3-10H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span class="hidden lg:inline">Keranjang</span><b>{{ array_sum(session('cart', [])) }}</b></a>
            </div>

            <!-- Mobile menu -->
            @include('partials.mobile-radial')
        </div>
    </header>

    @include('partials.storefront-palette')
    <!-- Main content -->
    <main id="main-content" class="flex-1" role="main">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-[#faf5f0] border-t border-neutral-200" role="contentinfo">
        <div class="container-siberkah py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="space-y-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary-600" aria-label="SIBERKAH - Beranda">
                        <img src="{{ asset('images/berkah-kreatif.webp') }}" width="44" height="44" alt="">
                        <span class="bk-wordmark font-bold text-xl">SIBERKAH</span>
                    </a>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        Penyewaan papan pengucapan, akrilik, dan dekorasi acara dengan kualitas terbaik dan harga terjangkau.
                    </p>
                    <div class="flex gap-4">
                        <a href="https://instagram.com/berkahkreatif_" target="_blank" rel="noopener noreferrer" class="text-neutral-400 hover:text-primary-600 transition-colors-150" aria-label="Instagram @berkahkreatif_">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.073 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.441s.645 1.441 1.441 1.441c.795 0 1.439-.645 1.439-1.441s-.644-1.441-1.439-1.441z"/>
                            </svg>
                        </a>
                        @if($contactLink = \App\Services\WhatsAppLinks::contact())
                        <a href="{{ $contactLink }}" target="_blank" rel="noopener noreferrer" class="text-neutral-400 hover:text-success-600 transition-colors-150" aria-label="WhatsApp">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20.52 3.48A11.914 11.914 0 0012 1.44 11.9 11.9 0 003.48 3.48C1.5 4.67.43 6.55.38 8.49c-.04 1.55.5 3.36 1.71 4.62l-1.6 5.76c-.26.94.16 1.86 1.06 2.1.88.24 1.74-.08 2.09-.64l4.48-3.51c1.5 1.72 3.9 2.94 6.52 2.94 1.58 0 3.14-.31 4.59-.9l4.07 3.24c.35.28.77.17 1.09-.19.9-.9.97-2.32.47-3.31l-1.42-4.55c1.03-.63 1.94-1.52 2.34-2.65A11.92 11.92 0 0020.52 3.48z"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>

                <div><h3 class="font-semibold mb-3">Koleksi</h3><ul class="space-y-2">
                    @foreach(['sewa'=>'Papan sewa','jual'=>'Produk siap jual','custom'=>'Pesanan custom'] as $jenis=>$nama)<li><a href="{{ route('catalog.index',['jenis'=>$jenis]) }}">{{ $nama }}</a></li>@endforeach
                </ul></div>
                <div><h3 class="font-semibold mb-3">Informasi</h3><ul class="space-y-2"><li><a href="{{ route('syarat-sewa') }}">Syarat sewa</a></li><li><a href="{{ route('privasi') }}">Kebijakan privasi</a></li></ul></div>
                <div><h3 class="font-semibold mb-3">Bantuan</h3><a href="{{ route('kontak') }}">Hubungi Berkah Kreatif</a></div>
            </div>
            <div class="mt-8 pt-8 border-t border-neutral-200 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-neutral-500">
                    &copy; {{ date('Y') }} SIBERKAH - Berkah Kreatif. Hak cipta dilindungi.
                </p>

            </div>
        </div>
    </footer>

    @include('partials.mobile-dock')
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[var(--z-toast)] flex flex-col gap-2" role="region" aria-label="Notifikasi" aria-live="polite"></div>

    @yield('scripts')
</body>
</html>
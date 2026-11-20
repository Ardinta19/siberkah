<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIBERKAH - Staf')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>
<body data-workspace class="min-h-screen bg-neutral-50 dark:bg-neutral-950 text-neutral-900 dark:text-neutral-50">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile sidebar overlay -->
        <div data-shade hidden class="fixed inset-0 bg-black/50 z-[var(--z-sticky)] lg:hidden" aria-hidden="true"></div>

        <!-- Sidebar -->
        <aside id="role-sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 z-[var(--z-fixed)] transform lg:relative lg:translate-x-0 dark:bg-neutral-900 dark:border-neutral-800" aria-label="Sidebar navigasi">
            <button data-sidebar-close class="bk-quiet lg:hidden" type="button">Tutup navigasi</button>
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center gap-2 px-4 py-4 border-b border-neutral-200 dark:border-neutral-800">
                    <svg class="w-8 h-8 text-primary-600" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect width="48" height="48" rx="12" fill="currentColor"/>
                        <path d="M12 24L20 32L36 16" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="bk-wordmark font-bold text-xl">SIBERKAH</span>
                </div>

                <!-- User info -->
                <div class="px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
                    <div class="flex items-center gap-3">
                        <div class="avatar avatar-md bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                            {{ strtoupper(auth('staff')->user()->nama[0] ?? 'S') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ auth('staff')->user()->nama ?? 'Staf' }}</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 capitalize">{{ auth('staff')->user()->peran ?? 'staf' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Navigasi staf">
                    @if(in_array(auth('staff')->user()->peran,['owner','admin','staf_produksi'],true))
                    <a href="{{ route('staf.produksi.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20" >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Produksi
                    </a>
                    @endif
                    @if(in_array(auth('staff')->user()->peran,['owner','admin','staf_kurir'],true))
                    <a href="{{ route('staf.kurir.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20" >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        Kurir
                    </a>
                    @endif
                    <a href="{{ route('catalog.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150 dark:text-neutral-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20" >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Katalog
                    </a>
                </nav>

                <!-- Logout -->
                <div class="p-3 border-t border-neutral-200 dark:border-neutral-800">
                    <form action="{{ route('staff.logout') }}" method="POST" class="block">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-danger-600 hover:bg-danger-50 rounded-xl transition-colors-150 dark:text-danger-400 dark:hover:bg-danger-900/20" >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
            <!-- Top bar -->
            <header id="main-header" class="sticky top-0 z-[var(--z-sticky)] bg-white/95 backdrop-blur-sm border-b border-neutral-200 dark:bg-neutral-900/95 dark:border-neutral-800 transition-shadow-200">
                @include('partials.expand-pill')
                <div class="container-siberkah bk-full-nav">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center gap-4">
                            <button type="button" class="lg:hidden p-2 rounded-lg text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition-colors-150" aria-label="Buka sidebar" aria-controls="role-sidebar" aria-expanded="false" data-sidebar-toggle>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                            <h1 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">@yield('page-title', 'Dashboard Staf')</h1>
                        </div>

                        <div class="flex items-center gap-2">
                            <button hidden data-role-command class="bk-palette-key" aria-haspopup="dialog" aria-label="Cari menu kerja">⌕ <kbd>Ctrl K</kbd></button>
                            <div class="hidden sm:flex items-center gap-2 px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-xl">
                                <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ auth('staff')->user()->nama ?? 'Staf' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="flex-1 overflow-auto" role="main">
                <span id="bk-scroll-marker" aria-hidden="true"></span>
                <div class="container-siberkah py-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <div id="toast-container" class="fixed bottom-6 right-6 z-[var(--z-toast)] flex flex-col gap-2" role="region" aria-label="Notifikasi" aria-live="polite"></div>

    @yield('scripts')
@include('partials.workspace-command')
</body>
</html>
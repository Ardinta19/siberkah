<div inert class="bk-mobile-sheet" id="mobile-menu" role="navigation" aria-label="Menu mobile">
                <div class="bk-mobile-clip"><div class="bk-mobile-links"><p class="kc-kicker">Jelajahi Berkah Kreatif</p>
                    <a href="{{ route('catalog.index') }}" @if(request()->routeIs('catalog.*')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Katalog
                    </a>
                    <a href="{{ route('syarat-sewa') }}" @if(request()->routeIs('syarat-sewa')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Syarat Sewa
                    </a>
                    <a href="{{ route('privasi') }}" @if(request()->routeIs('privasi')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Privasi
                    </a>
                    <a href="{{ route('kontak') }}" @if(request()->routeIs('kontak')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                        Kontak
                    </a>
                    @auth('customer')
                        <a href="{{ route('pesanan.index') }}" @if(request()->routeIs('pesanan.index')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-neutral-600 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                            Pesanan Saya
                        </a>
                        @if(Route::has('otp.logout'))
                        <form action="{{ route('otp.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-base font-medium text-neutral-600 hover:text-danger-600 hover:bg-danger-50 rounded-xl transition-colors-150">
                                Keluar
                            </button>
                        </form>
                        @endif
                    @else
                        <a href="{{ route('otp.form') }}" @if(request()->routeIs('otp.form')) aria-current="page" @endif class="block px-3 py-2 text-base font-medium text-primary-600 hover:bg-primary-50 rounded-xl transition-colors-150">
                            Masuk / Daftar
                        </a>
                    @endauth
                </div></div>
            </div>

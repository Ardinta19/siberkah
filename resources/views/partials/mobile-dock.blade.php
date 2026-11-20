<nav class="kc-dock" aria-label="Navigasi cepat">
@foreach([['home','Beranda','M3 10l9-7 9 7v10h-6v-7H9v7H3z'],['catalog.index','Katalog','M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],['pesanan.index','Lacak','M2 5h12v11H2z M14 9h4l4 4v3h-8 M8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0 M21 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0'],[auth('customer')->check()?'profile.edit':'otp.form','Akun','M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M4 21a8 8 0 0 1 16 0']] as [$route,$label,$path])
<a href="{{ route($route) }}" @if(request()->routeIs($route) || ($label === 'Katalog' && request()->routeIs('catalog.show'))) aria-current="page" @endif><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}"/></svg>{{ $label }}</a>
@endforeach
</nav>

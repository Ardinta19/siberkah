@if($paginator->hasPages())
<nav class="bk-pages" aria-label="Halaman {{ $paginator->getPageName() === 'antrean' ? 'antrean' : 'hasil' }}">
@if($paginator->onFirstPage())<span aria-disabled="true">‹ Sebelumnya</span>@else<a rel="prev" href="{{ $paginator->previousPageUrl() }}">‹ Sebelumnya</a>@endif
@foreach($elements as $element)
@if(is_string($element))<span aria-hidden="true">…</span>@else
@foreach($element as $page=>$url)
@if($page === $paginator->currentPage())<span aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>@endif
@endforeach
@endif
@endforeach
@if($paginator->hasMorePages())<a rel="next" href="{{ $paginator->nextPageUrl() }}">Berikutnya ›</a>@else<span aria-disabled="true">Berikutnya ›</span>@endif
@if($paginator->lastPage() > 4)
<details class="bk-jump"><summary>Lompat ke halaman</summary><form method="get" action="{{ $paginator->path() }}{{ $paginator->fragment() ? '#'.$paginator->fragment() : '' }}">
@foreach(request()->query() as $name=>$value)@if($name !== $paginator->getPageName() && is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
<label>Halaman 1–{{ $paginator->lastPage() }} <input type="number" name="{{ $paginator->getPageName() }}" min="1" max="{{ $paginator->lastPage() }}" required value="{{ $paginator->currentPage() }}"></label><button class="kc-action">Pergi</button></form></details>
@endif
</nav>
@endif

<nav class="bk-history-filters" aria-label="Pengelolaan produk ini">
    @foreach(['edit'=>'Data produk','variants'=>'Varian','gallery'=>'Galeri foto'] + (($product->jenis ?? $produk->jenis ?? '') === 'sewa' ? ['units'=>'Unit sewa'] : ['options'=>'Opsi checkout']) as $section=>$label)
        <a href="{{ route('admin.katalog.'.$section,['id'=>$productId]+$context) }}" @if(request()->routeIs('admin.katalog.'.$section)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

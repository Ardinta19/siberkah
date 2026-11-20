<dialog id="bk-palette" class="bk-palette" aria-labelledby="palette-title">
  <div class="bk-sheet-grip" aria-hidden="true"></div>
  <header><div><p class="kc-kicker">Berkah Kreatif · Katalog</p><h2 id="palette-title">Cari produk</h2></div><button type="button" data-dismiss aria-label="Tutup pencarian">×</button></header>
  <form action="{{ route('catalog.index') }}#produk-katalog" role="search">
    @include('partials.product-search-fields',['prefix'=>'palette'])
    <section data-search-selection></section>
    <footer class="bk-search-actions"><button type="button" data-reset-filters>Reset filter</button><button class="kc-action">Cari produk</button></footer>
  </form>
</dialog>

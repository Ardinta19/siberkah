<dialog id="bk-palette" class="bk-palette" aria-labelledby="palette-title">
  <div class="bk-sheet-grip" aria-hidden="true"></div>
  <header><div><p class="kc-kicker">Berkah Kreatif · Katalog</p><h2 id="palette-title">Cari produk</h2></div><button type="button" data-dismiss aria-label="Tutup pencarian">×</button></header>
  <form action="{{ route('catalog.index') }}#produk-katalog" role="search">
    @if(request()->routeIs('catalog.index'))
      @foreach(request()->only(['kategori','jenis','tanggal','urut']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    @endif
    <label for="palette-query">Nama produk</label>
    <div><input id="palette-query" name="q" type="search" maxlength="150" placeholder="Contoh: buket mawar" value="{{ request()->routeIs('catalog.index') ? request('q') : '' }}" autocomplete="off"><button class="kc-action">Cari produk</button></div>
  </form>
</dialog>

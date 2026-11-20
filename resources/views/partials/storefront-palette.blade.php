<dialog id="bk-palette" class="bk-palette" aria-labelledby="palette-title">
  <div class="bk-sheet-grip" aria-hidden="true"></div>
  <header><div><p class="kc-kicker">Berkah Kreatif · Akses cepat</p><h2 id="palette-title">Mau ke mana?</h2></div><button type="button" data-dismiss aria-label="Tutup pencarian">×</button></header>
  <form action="{{ route('catalog.index') }}" role="search">
    <label for="palette-query">Cari produk atau halaman</label>
    <div><input id="palette-query" name="q" type="search" maxlength="150" placeholder="Misalnya: buket atau keranjang" autocomplete="off"><button class="kc-action">Cari produk</button></div>
  </form>
  <p class="kc-kicker">Pindah halaman</p>
  <nav aria-label="Tujuan cepat">@foreach(['home'=>'Beranda','catalog.index'=>'Katalog','cart.index'=>'Keranjang','pesanan.index'=>'Lacak pesanan','kontak'=>'Kontak',(auth('customer')->check() ? 'profile.edit' : 'otp.form')=>'Akun'] as $dest=>$label)<a href="{{ route($dest) }}">{{ $label }} <span aria-hidden="true">↗</span></a>@endforeach</nav>
  <p data-no-dest hidden>Tidak ada halaman yang cocok. Gunakan “Cari produk” untuk mencari di katalog.</p>
</dialog>

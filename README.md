# SIBERKAH — Berkah Kreatif

Aplikasi Laravel untuk katalog, pemesanan buket, penyewaan papan, pembayaran, dan operasional staf.

## Peta proyek

- `routes/web.php` → URL, nama route, middleware peran, dan controller.
- `app/Http/Controllers/` → memilih halaman Blade atau respons JSON; `app/Actions/` → proses bisnis.
- `resources/views/` → sumber tampilan. Gunakan struktur berikut untuk halaman baru:

| Folder | Isi |
|---|---|
| `publik/` | Katalog serta halaman informasi/kebijakan tanpa login |
| `pelanggan/` | Auth, profil, checkout, pesanan dan pembayaran pelanggan |
| `admin/` | Dasbor dan keuangan bersama Admin/Owner |
| `owner/` | Pengelolaan katalog khusus Owner |
| `staf/` | Auth internal dan agenda operasional kurir/produksi |
| `layouts/`, `components/`, `partials/` | Kerangka halaman, komponen Blade dan potongan bersama |

Nama view memakai titik untuk **folder**: `pelanggan.pesanan.show` menunjuk ke `pelanggan/pesanan/show.blade.php`. Nama route tidak harus sama dengan nama view. Hak akses ditentukan di server, bukan oleh nama folder. File di `storage/framework/views/` adalah hasil kompilasi, bukan sumber desain.

- `resources/css/`, `resources/js/` → aset antarmuka; `ui-ux/` dan aset di `docs/` → referensi desain, bukan halaman aplikasi.
- `database/`, `config/`, `bootstrap/`, `public/`, `storage/` → skema, konfigurasi dan runtime aplikasi. Jangan commit sesi, cache atau unggahan pengguna.
- `tests/`, `scripts/ci/`, `docker/`, `.github/workflows/` → pengujian, simulasi browser, dan latihan pemulihan.
- [Spesifikasi](docs/SRS.html) · [Kesiapan backend](docs/backend/kesiapan.md) · [Panduan uji operator](docs/backend/uji-operator.md).

Riwayat sebelum penataan views: commit `f0d829d`. Prototipe Blade lama yang tidak terhubung ke aplikasi dapat ditinjau di commit tersebut.

Standar visual dan navigasi: [Pola interaksi SIBERKAH](docs/desain/pola-interaksi.md).

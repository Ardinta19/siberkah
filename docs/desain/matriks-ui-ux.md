# Matriks audit UI/UX per halaman

**Status: belum sign-off.** Satu baris per tujuan HTML GET di `routes/web.php`, bukan per tombol, endpoint tulis atau partial Blade. Nama rute adalah kunci stabil; isi satu halaman bisa berbeda menurut peran, status transaksi dan flag staging. Akses akun pelanggan diperlukan mulai checkout, profil, riwayat, detail dan pembayaran. Masuk staf hanya untuk tamu staf; ganti sandi untuk sesi staf aktif.

**Cara membaca:** “Lulus ukuran” berarti skenario Chrome pada 320/768/1440 px tidak menemukan luapan horizontal pada kontainer yang diukur, bukan seluruh kondisi lulus UX. “Alur saja” berarti interaksi utama ada pada uji browser, tetapi belum ditandai lulus ukuran di sini. Bukti: `scripts/ci/checkout-browser.py` (kelompok screens, auth_page, owner_urls, urls, producer dan courier). Hak akses diuji terpisah di `tests/Feature/RouteAccessMatrixTest.php`.

## Publik & pelanggan

| Rute | Halaman | Hasil terbatas |
|---|---|---|
| `home` | Beranda | Lulus ukuran |
| `catalog.index` | Katalog | Lulus ukuran |
| `catalog.show` | Detail produk | Lulus ukuran |
| `cart.index` | Keranjang | Lulus ukuran |
| `otp.form` | Masuk melalui OTP | Lulus ukuran |
| `syarat-sewa` | Syarat sewa | Lulus ukuran |
| `privasi` | Privasi | Lulus ukuran |
| `kontak` | Kontak | Lulus ukuran |
| `checkout.product` | Checkout produk | Lulus ukuran |
| `profile.edit` | Profil pelanggan | Lulus ukuran |
| `pesanan.index` | Riwayat pesanan | Lulus ukuran |
| `pesanan.show` | Detail pesanan dan jaminan | Lulus ukuran |
| `payment.show` | Tagihan dan petunjuk bayar | Lulus ukuran |

## Identitas staf

| Rute | Halaman | Hasil terbatas |
|---|---|---|
| `staff.login` | Masuk staf | Lulus ukuran |
| `staff.password.form` | Ganti kata sandi | Lulus ukuran |

## Admin & Owner

| Rute | Halaman | Hasil terbatas |
|---|---|---|
| `dashboard.hari-ini` | Dasbor operasional | Lulus ukuran |
| `admin.pesanan.index` | Daftar pesanan | Lulus ukuran |
| `admin.pesanan.show` | Rincian pesanan | Lulus ukuran |
| `admin.permohonan-batal.index` | Permohonan pembatalan | Lulus ukuran |
| `kas-antar.index` | Verifikasi pembayaran dan setoran | Lulus ukuran |
| `kas-antar.history` | Riwayat setoran | Lulus ukuran |
| `admin.rekonsiliasi.index` | Rekonsiliasi mutasi | Lulus ukuran |
| `admin.pesanan.unit` | Penggantian unit | Lulus ukuran |
| `retur.inspeksi` | Inspeksi dan penutupan retur | Lulus ukuran |
| `admin.kalender` | Kalender operasional | Lulus ukuran |
| `admin.inventaris` | Inventaris sewa | Lulus ukuran |
| `admin.inventaris.units` | Pengelolaan unit | Lulus ukuran |
| `admin.pesanan.manual-sewa` | Sewa manual | Lulus ukuran |
| `toko.form` | Penjualan tunai di toko | Lulus ukuran |
| `admin.buket.pilih` | Pilih buket manual | Perlu perbaikan: alur multi-produk belum tersedia |
| `admin.buket.form` | Checkout buket manual | Perlu perbaikan: satu produk per pesanan |

## Owner

| Rute | Halaman | Hasil terbatas |
|---|---|---|
| `admin.katalog.index` | Kelola katalog | Lulus ukuran |
| `admin.katalog.create` | Buat draf | Lulus ukuran |
| `admin.katalog.edit` | Edit produk | Lulus ukuran |
| `admin.katalog.gallery` | Galeri produk | Lulus ukuran |
| `admin.katalog.variants` | Varian | Lulus ukuran |
| `admin.katalog.options` | Opsi personalisasi | Lulus ukuran |
| `admin.katalog.units` | Unit sewa | Lulus ukuran |
| `admin.pengaturan.index` | Pengaturan | Lulus ukuran |
| `admin.pengaturan.kalender` | Kalender toko | Lulus ukuran |
| `tujuan.index` | Tujuan pembayaran | Lulus ukuran |
| `admin.aktivitas.index` | Log aktivitas | Lulus ukuran |
| `admin.laporan.index` | Laporan | Lulus ukuran |
| `laporan.penerimaan` | Penerimaan terverifikasi | Lulus ukuran |

## Staf Produksi / Kurir (juga Admin & Owner)

| Rute | Halaman | Hasil terbatas |
|---|---|---|
| `staf.produksi.index` | Agenda produksi dan sunting papan | Lulus ukuran |
| `staf.kurir.index` | Agenda serah, jemput dan inspeksi | Lulus ukuran |

## Bukan halaman

`admin.bukti-transfer`, `admin.katalog.images`, `admin.laporan.export-csv`, `laporan.penerimaan.csv`, `papan.show`, `pesanan.cancellation-status`, `retur.foto`: JSON, bukti berkas privat atau unduhan CSV. Uji fungsi dan izin tetap diperlukan, tetapi tidak dihitung sebagai layar HTML.

## Gerbang sebelum sign-off

1. Untuk setiap baris: uji keyboard (Tab, Shift+Tab, Escape, fokus setelah dialog), nama kontrol dengan pembaca layar, kontras teks/fokus, zoom 200%, serta pesan kesalahan yang dapat ditemukan tanpa warna. **Belum ada sign-off menyeluruh** untuk butir ini.
2. Uji kondisi relevan: kosong, terisi, memuat, input salah, respons hilang, sesi berakhir dan akses peran lain. Catat rute, peran, kondisi, langkah, perangkat/browser dan hasil; jangan gunakan data pelanggan nyata.
3. Tutup celah buket manual multi-produk. Gerbang `admin_layout` mencakup kalender dengan unit dipilih, inventaris terfilter, editor unit, formulir sewa hari ini terisi, serta pencarian pelanggan kasir dan pratinjau penjualan tanpa menyimpan transaksi. Verifikasi kamera, jaringan seluler dan serah fisik mengikuti [lembar Kurir](penerimaan-kurir.md).
4. Halaman error framework, state modal, panel dan variasi produk bukan rute terpisah; audit sebagai keadaan halaman asal. Tambah baris bila tujuan HTML baru ditambahkan.

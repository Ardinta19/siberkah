# Standar Coding SIBERKAH

## Bahasa
- **Semua kode menggunakan Bahasa Indonesia** untuk:
  - Nama method (misal: `bisaMengelolaKatalog`, `adakahPesananAktif`)
  - Nama variabel (misal: `$nomorPesanan`, `$daftarProduk`, `$adakahStok`)
  - Comment dan PHPDoc
  - Pesan error/validasi
  - Log message

## Naming Convention

### Model
- Nama class: `StudlyCase` bahasa Indonesia
  - `Pelanggan`, `Pengguna`, `Produk`, `Pesanan`, `ItemPesanan`
- Tabel database: `snake_case` bahasa Indonesia
  - `pelanggan`, `pengguna`, `produk`, `pesanan`, `item_pesanan`

### Enum
- Nama class: `StudlyCase` bahasa Indonesia
  - `StatusPesanan`, `StatusPembayaran`, `JenisProduk`, `PeranPengguna`
- Case value: `lowercase` dengan `_` separator
  - `MENUNGGU_PEMBAYARAN`, `DIKONFIRMASI`, `SEWA`, `CUSTOM`

### Method
- Prefix boolean: `bisa`, `adakah`, `apakah`, `harus`, `sudah`
  - `bisaMengelolaKatalog()`, `adakahStok()`, `apakahSudahBayar()`
- Prefix getter: `ambil`, `dapatkan`
  - `ambilProdukTersedia()`, `dapatkanTotalHarga()`
- Prefix action: `buat`, `ubah`, `hapus`, `simpan`, `proses`
  - `buatPesanan()`, `ubahStatus()`, `simpanLog()`

### Variable
- `snake_case` bahasa Indonesia
  - `$nomor_pesanan`, `$daftar_produk`, `$adakah_stok`, `$total_harga`
- Boolean prefix: `adakah_`, `sudah_`, `bisa_`, `harus_`
  - `$adakah_stok`, `$sudah_bayar`, `$bisa_dipesan`

### Scope
- Prefix: `hanya_`, `dengan_`, `untuk_`
  - `scopeHanyaAktif()`, `scopeDenganPeran()`, `scopeUntukPelanggan()`

### Constant
- `UPPER_SNAKE_CASE` bahasa Indonesia
  - `KODE_UNIK_MIN`, `KODE_UNIK_MAX`, `BATAS_KECEPATAN_OTP`

## Struktur File

```
app/
├── Enums/           # Enum classes (bahasa Indonesia)
├── Models/          # Eloquent Models
│   ├── Identitas/
│   ├── Katalog/
│   ├── UnitSewa/
│   ├── Acuan/
│   ├── Pesanan/
│   ├── Reservasi/
│   ├── Pembayaran/
│   └── Log/
├── Actions/         # Action Classes (single responsibility)
│   ├── Auth/
│   ├── Katalog/
│   ├── Pemesanan/
│   ├── Pembayaran/
│   ├── Operasional/
│   ├── KatalogPengaturan/
│   └── Laporan/
├── Http/
│   ├── Requests/    # Form Requests
│   │   ├── Auth/
│   │   ├── Pemesanan/
│   │   ├── Pembayaran/
│   │   ├── Operasional/
│   │   ├── Katalog/
│   │   └── Pengaturan/
│   └── Resources/   # API Resources
└── Traits/          # Shared Traits
```

## PHPDoc Standard

```php
/**
 * Deskripsi singkat dalam Bahasa Indonesia.
 *
 * @param string|Enum $parameter Deskripsi parameter
 * @return TipeReturn Deskripsi return
 */
public function namaMethod(string|Enum $parameter): TipeReturn
{
    // implementation
}
```

## Validation Rules

- Pesan error dalam Bahasa Indonesia
- Gunakan attribute nama field bahasa Indonesia di validation.php

## Database

- Nama kolom: `snake_case` bahasa Indonesia
  - `nomor_wa`, `masuk_terakhir_pada`, `tahan_hingga_pada`
- Foreign key: `{tabel}_id` (misal: `pelanggan_id`, `produk_id`)

## Route & Controller

- Route name: `snake_case` bahasa Indonesia
  - `pelanggan.pesanan.index`, `admin.katalog.store`
- Controller method: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
  - Custom method: bahasa Indonesia (misal: `verifikasiPembayaran`, `catatPenyesuaian`)

---

*Dokumen ini wajib diikuti untuk seluruh pengembangan SIBERKAH.*
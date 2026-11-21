# Peta kerja UI/UX per peran

Satu cabang UI/UX; unit penerimaan adalah alur per peran, bukan tombol. Ini indeks kerja; acuan formal tetap `docs/SRS.html`. Bukti otomatis tidak menggantikan penerimaan manual.

| Paket Pelanggan | Hasil yang harus dicapai | Bukti otomatis di repo |
|---|---|---|
| Temukan produk (KAT/KTL) | Cari nama, saring, buka detail/galeri, kembali ke daftar semula | `checkout-browser.py`, `CatalogAvailabilityTest` |
| Pilih dan pesan (PSN/STK) | Keranjang, kuantitas, tanggal, opsi, harga dan ongkir; persetujuan profil tidak memutus checkout | `checkout-browser.py`, `CatalogPublicationTest`, `BouquetCheckoutTest`, `RentalCheckoutTest` |
| Bayar (BYR) | Tunai saat serah atau DP bank dan sisa tunai; kode unik bukan pokok | `checkout-browser.py`, `BouquetCheckoutTest` |
| Periksa pesanan (AKN/PSN) | Riwayat, detail historis, tagihan, status dan permohonan pembatalan | `checkout-browser.py`, `BouquetCheckoutTest` |
| Kelola akun (AUTH/AKN) | Profil, versi persetujuan, akses hanya milik sendiri | `IdentityFlowTest`, `AnonymizeCustomerTest` |

Urutan paket berikutnya: Owner (katalog, inventaris, pengaturan dan laporan); Admin (pesanan, pembayaran dan rekonsiliasi); Staf Produksi (antrean hingga siap); Kurir (antar/jemput, tunai dan setoran). Periksa alur serah-terima lintas peran tanpa membuat cabang/PR baru. Alur login final, pengiriman OTP nyata dan uji manual formal tetap ditunda. Jangan menyatakan semua skenario lulus hanya karena satu suite CI hijau.

## Kontrak visual untuk setiap FR

Sebelum menutup satu alur, terapkan [standar interaksi lintas layout](pola-interaksi.md) pada halaman awal, editor, detail, keadaan kosong, dan hasil akhirnya. Halaman baru harus mewarisi salah satu layout guest/customer/admin/staff, bukan menyalin shell sendiri.

- Desktop: pill lengkap berubah menjadi satu logo yang dapat dibuka lagi, palet dengan keyboard, popover untuk pilihan lokal, pencarian produk di upper sheet. Palet ruang kerja hanya menawarkan tujuan sesuai peran.
- Ponsel: tab bawah untuk tujuan rutin, hamburger untuk navigasi radial, bottom sheet untuk pencarian. Jangan menduplikasi tindakan transaksi di dock. Tablet mengikuti ruang tersedia, bukan sekadar memperkecil desktop.
- Daftar panjang: jump page di kedua ukuran. Pada tindakan berisiko atau informasi tagihan, utamakan teks opak dan konfirmasi yang jelas; glass untuk lapisan navigasi. React untuk kontrol yang memiliki state, bukan untuk mengganti tautan biasa. Escape, pengembalian fokus, safe area dan preferensi gerak merupakan bagian dari kriteria fungsional.

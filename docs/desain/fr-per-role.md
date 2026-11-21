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

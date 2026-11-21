# Audit penutupan UI Admin — staging

**Keputusan: paket tetap terbuka.** Daftar ini membandingkan UC yang menyebut Admin pada SRS dengan jalur yang dapat dipakai dari browser. Adanya POST, tes Owner, atau halaman yang bisa dibuka dengan ID yang diketahui bukan bukti Admin dapat menyelesaikan tugas dari dasbor.

| SRS · tugas | Bukti tersedia | Keputusan / pekerjaan tersisa |
|---|---|---|
| UC-10 · masuk staf | Chrome masuk sebagai Admin asli dan menelusuri ruang kerja | Teruji untuk akun CI, bukan penerimaan autentikasi produksi |
| UC-11, UC-18 · verifikasi dan selisih | Chrome: mutasi, respons hilang, pencarian dan pengembalian. Feature: keputusan selisih dan penolakan pokok kurang | Pertahankan batas pokok, catatan keputusan, dan hak akses |
| UC-19 · pelunasan dan tunai | Chrome: DP bank, serah tunai, pemeriksaan setoran; backend: koreksi kumulatif | Jangan gabungkan setoran dengan pembayaran baru |
| UC-20 · refund dan jaminan | Feature: pembatalan/refund; Chrome: penilaian, penerimaan deposit dan pemakaian khusus Owner | Refund pembatalan teruji di server; tidak sama dengan transfer bank nyata |
| UC-21 / OPS-05 · pesanan WhatsApp/toko | `admin.pesanan.manual-buket`, `manual-store`, `toko.store`; tes manual pada BouquetCheckoutTest dan RentalCheckoutTest | **Celah UI:** formulir dari ruang kerja, pelanggan/izin, stok/jadwal, hasil dan konflik. Bedakan transaksi langsung dari pesanan WA. Jangan membuka pengecualian bisnis tanpa keputusan eksplisit |
| UC-22 / OPS-06 · kalender, kapasitas, blokir | Kalender diuji sebagai Owner. Blokir unit punya endpoint operasional | **Celah peran:** GET kalender saat ini khusus Owner. Siapkan akses operasional sempit untuk Admin, termasuk peringatan benturan; jangan membuka pengaturan tarif atau hak melepas blokir Owner secara implisit |
| UC-24 · inventaris fisik | UI inventaris Owner dan penggantian reservasi per item Admin tersedia | **Celah peran:** GET editor inventaris khusus Owner; POST manage-units tersedia di grup Owner/Admin. Admin butuh titik masuk, pemilihan produk dan pengelolaan kondisi tanpa akses publikasi katalog |
| UC-25 · agenda | Dasbor, produksi dan antar dari Admin dipakai Chrome | Pengujian sebagai Admin tidak menutup paket Staf Produksi atau Kurir |
| UC-28 · pembayaran terlambat | Feature: T13/T14, stok sudah diambil, tidak ada kredit ganda | Tersedia pada antrean; bukan tombol generic untuk melewati status |

## Gerbang layar

`checkout-browser.py` mengunjungi 9 tujuan GET menggunakan sesi **Admin**, bukan Owner: dasbor, daftar/detail pesanan, permohonan batal, pembayaran/setoran, riwayat setoran, rekonsiliasi, unit per item dan bukti retur. ID dan tautan riwayat berasal dari transaksi/halaman aktual. Uji tujuan akhir, satu `main h1`, serta overflow kontainer `main` pada 320/768/1440. Pemeriksaan geometri bukan uji kontras, pembaca layar, semua kondisi transaksi atau perangkat fisik.

## Urutan keluar

1. UC-21: buat satu pesanan manual dari browser hingga dapat dilacak; uji permintaan ganda, akses, consent dan kegagalan ketersediaan.
2. UC-22 dan UC-24: putuskan/terapkan batas Admin vs Owner, uji navigasi dan larangan lintas peran.
3. Cocokkan aksi status terkait (terutama T04, T10, T12) dengan tombol kontekstual; bedakan server-only dari UI yang dapat digunakan. Tambahkan layar baru ke gerbang sebelum sign-off. Lanjut Produksi dan Kurir setelah celah diselesaikan atau penundaan disepakati.

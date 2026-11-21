# Audit penutupan UI Admin — staging

**Keputusan: paket tetap terbuka.** Daftar ini membandingkan UC yang menyebut Admin pada SRS dengan jalur yang dapat dipakai dari browser. Adanya POST, tes Owner, atau halaman yang bisa dibuka dengan ID yang diketahui bukan bukti Admin dapat menyelesaikan tugas dari dasbor.

| SRS · tugas | Bukti tersedia | Keputusan / pekerjaan tersisa |
|---|---|---|
| UC-10 · masuk staf | Chrome masuk sebagai Admin asli dan menelusuri ruang kerja | Teruji untuk akun CI, bukan penerimaan autentikasi produksi |
| UC-11, UC-18 · verifikasi dan selisih | Chrome: mutasi, respons hilang, pencarian dan pengembalian. Feature: keputusan selisih dan penolakan pokok kurang | Pertahankan batas pokok, catatan keputusan, dan hak akses |
| UC-19 · pelunasan dan tunai | Chrome: DP bank, serah tunai, pemeriksaan setoran; backend: koreksi kumulatif | Jangan gabungkan setoran dengan pembayaran baru |
| UC-20 · refund dan jaminan | Feature: pembatalan/refund; Chrome: penilaian, penerimaan deposit dan pemakaian khusus Owner | Refund pembatalan teruji di server; tidak sama dengan transfer bank nyata |
| UC-21 / OPS-05 · pesanan WhatsApp/toko | `admin.pesanan.manual-buket`, `manual-store`, `toko.store`; tes manual pada BouquetCheckoutTest dan RentalCheckoutTest | Sewa manual, buket satu produk dan kasir standar sudah terhubung ke ruang kerja. UI keranjang manual campuran belum tersedia. Bedakan transaksi langsung dari pesanan WA. Jangan membuka pengecualian bisnis tanpa keputusan eksplisit |
| UC-22 / OPS-06 · kalender, kapasitas, blokir | Kalender diuji sebagai Owner. Blokir unit punya endpoint operasional | Kalender operasional staging Admin tersedia terpisah dari Pengaturan Owner. Chrome: ubah kapasitas, blokir dan buka satu unit pada tanggal pilihan. Feature: reservasi tidak hilang dan pengaturan Owner tetap ditolak. |
| UC-24 · inventaris fisik | UI inventaris Owner dan penggantian reservasi per item Admin tersedia | UC-24 staging: dasbor Admin → inventaris khusus produk sewa → editor unit bersama. Chrome memeriksa pendaftaran, perubahan kondisi, kembali ke pencarian semula dan penolakan katalog Owner. |
| UC-25 · agenda | Dasbor, produksi dan antar dari Admin dipakai Chrome | Pengujian sebagai Admin tidak menutup paket Staf Produksi atau Kurir |
| UC-28 · pembayaran terlambat | Feature: T13/T14, stok sudah diambil, tidak ada kredit ganda | Tersedia pada antrean; bukan tombol generic untuk melewati status |

## Gerbang layar

`checkout-browser.py` mengunjungi 9 tujuan GET menggunakan sesi **Admin**, bukan Owner: dasbor, daftar/detail pesanan, permohonan batal, pembayaran/setoran, riwayat setoran, rekonsiliasi, unit per item dan bukti retur. ID dan tautan riwayat berasal dari transaksi/halaman aktual. Uji tujuan akhir, satu `main h1`, serta overflow kontainer `main` pada 320/768/1440. Pemeriksaan geometri bukan uji kontras, pembaca layar, semua kondisi transaksi atau perangkat fisik.

## Urutan keluar

1. UC-21: buat satu pesanan manual dari browser hingga dapat dilacak; uji permintaan ganda, akses, consent dan kegagalan ketersediaan.
2. UC-22 dan UC-24: putuskan/terapkan batas Admin vs Owner, uji navigasi dan larangan lintas peran.
3. Cocokkan aksi status terkait (terutama T04, T10, T12) dengan tombol kontekstual; bedakan server-only dari UI yang dapat digunakan. Tambahkan layar baru ke gerbang sebelum sign-off. Lanjut Produksi dan Kurir setelah celah diselesaikan atau penundaan disepakati.

**Kemajuan UC-21:** daftar pesanan → pencarian pelanggan terverifikasi dengan nomor persis → formulir sewa antar tanggal mendatang → rincian pesanan. Aturan transaksi/replay sama dengan API. Buket, transaksi langsung dan sewa hari yang sama bukan bagian formulir ini.

**UC-21 sewa hari ini:** pilih pelanggan → Hari ini. Hanya produk berizin, spidol/templat, pembayaran penuh dan slot 07.00–17.00 WIB minimal tiga jam ke depan. Saat slot habis, arahkan ke tanggal mendatang. Tanggal mendatang mengikuti cutoff bisnis. Pemeriksaan akhir stok/jadwal tetap pada checkout terkunci.

**UC-21 transaksi langsung:** Admin dapat memilih pelanggan dan varian siap jual tanpa opsi, mencocokkan total pembulatan dengan tunai fisik, lalu membuka rincian pesanan terbayar. Stok dan harga diperiksa ulang saat menyimpan. Penjualan ini tidak mewakili checkout buket/custom melalui WhatsApp.

**UC-21 buket:** dari daftar pesanan, cari pelanggan, pilih produk siap jual/custom dan isi checkout satu produk (beberapa unit, varian, opsi dan catatan). Harga serta kapasitas dihitung ulang oleh aksi bersama; POST manual terpisah dari sesi pelanggan dan keranjang. Admin juga dapat mencentang beberapa produk pada satu halaman pemilih dan menyusun satu pesanan bersama, tanpa mengubah keranjang pelanggan. Chrome memeriksa kedua halaman pada 320/768/1440 px. Uji fitur mencakup kombinasi siap jual/custom, opsi per unit, pembatalan seluruh transaksi bila baris kedua tidak valid dan pengiriman ulang tanpa reservasi ganda.

**UC-22 kalender Admin:** akses staging terpisah dari Pengaturan Owner. Dari dasbor → tanggal → kapasitas custom/blokir produk atau toko, atau kode unit persis untuk membaca reservasi dan blokir harian. Admin boleh melepas blokir unit, bukan blokir produk/toko Owner. Perubahan blokir tidak memindahkan reservasi.

**Aksi pada rincian:** T04 memakai pelepasan inventaris transaksional dan audit staf, ditahan bila konfirmasi atau mutasi belum tuntas. T12 memakai keputusan refund sistem (permohonan aktif diarahkan ke antrean). T10 hanya untuk buket/toko sudah diserahkan dan lunas. Bukan dropdown status generik.

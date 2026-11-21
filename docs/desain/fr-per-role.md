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

## Gerbang penerimaan antarmuka pelanggan

Matriks `scripts/ci/checkout-browser.py` memuat 13 halaman GET: beranda, katalog, detail, keranjang, checkout, profil, riwayat, detail pesanan, tagihan, syarat sewa, privasi, kontak, dan masuk. Pada setiap URL periksa tujuan tidak dialihkan, satu judul utama, dan lebar dokumen pada 320/768/1440 px. Jalankan masuk sebagai tamu; jangan kirim OTP atau transaksi ekstra untuk menguji layout. Partial biaya, status pembatalan, tujuan bayar, dialog dan kondisi kosong adalah bagian halaman induk, bukan halaman fiktif.

Penghapusan akun merupakan aksi terpisah dari menyimpan nama. Di lingkungan staging yang diizinkan, minta frasa konfirmasi dan tetap periksa OTP baru, transaksi terbuka serta rekonsiliasi pada server. Kegagalan HTML harus kembali ke profil; API mempertahankan kontraknya. Pengujian visual manual perangkat fisik, tujuan pembayaran riil, integrasi OTP akhir dan penerimaan operator tetap di luar sign-off UI otomatis ini.

**Owner — laporan dan aturan:** navigasi browser membuka halaman HTML, permintaan JSON dan unduhan CSV tetap terpisah. Arus kas bank bukan laba; jangan campur mutasi bank dengan pokok penerimaan atau setoran kurir. Ekspor mengikuti periode/rentang yang dilihat. Perubahan aturan bisnis dari formulir membawa nilai sebelumnya; bila berubah di tempat lain, tinjau ulang sebelum menulis.

**Owner — tarif dan audit:** zona dan tarif kerusakan dapat diubah satu kartu per PATCH hanya bila staging aktif. Bawa nilai sebelum edit, tangani konflik tanpa menimpa perubahan, dan kembali ke kartu terkait. Riwayat aktivitas baca-saja mendukung filter perubahan tarif dan paginasi; tampilkan pasangan nominal yang relevan, bukan dump payload log atau data pribadi.

**Owner — tujuan pembayaran:** dari Pengaturan, pisahkan form rekening bank dan QRIS agar kolom yang tidak relevan tidak pernah ikut terkirim. Simpan nonaktif terlebih dahulu, periksa penerima, lalu aktifkan lewat konfirmasi. Penggantian detail membuat tujuan baru; jangan menimpa identitas tujuan lama. Pemeriksaan merchant QRIS sungguhan tetap memerlukan operator.

**Owner — kalender operasional:** pilih tanggal dengan GET sebelum menulis. Bedakan kuota custom (bawaan 15) dari blokir toko/produk. Lihat tanggal lampau tanpa formulir mutasi. Blokir memerlukan alasan dan konfirmasi; nomor pesanan aktif yang terdampak adalah peringatan, bukan pembatalan. Validasi batas terbaru di bawah lock saat menyimpan kapasitas.

**Membuka blokir kalender:** Owner melepas satu entri spesifik pada tanggal sekarang/mendatang dengan alasan dan konfirmasi. Jangan menghapus semua entri tanggal, mengubah kuota, atau menjanjikan produk pasti tersedia. Simpan audit sebelum/sesudah; pengiriman ulang tidak menambah audit.

**Owner — opsi checkout:** untuk siap jual/custom, pisahkan catatan teks gratis, pilihan harga tetap dan tambahan satuan. Pertahankan tipe opsi lama; nonaktifkan nilai yang tidak dijual, jangan menghapus histori. Opsi harga wajib perlu nilai aktif; pemeriksaan versi mencakup nilai dan harga terkait. Sewa tetap memakai unit dan tanggal, bukan editor opsi buket.

**Gerbang UI Owner:** periksa 13 tujuan GET khusus Owner (daftar katalog, draf, edit, foto, varian, opsi, unit, pengaturan, kalender, tujuan bayar, aktivitas, arus kas, penerimaan). Ukur kontainer `main` yang benar-benar digulir pada 320/768/1440 px. Uji lintas peran terpisah: pilihan dan harga yang dibuat Owner harus dapat dipesan pelanggan dan muncul pada rincian historis. Aksi kelompok pengelolaan produk berbeda dari publikasi, arsip, dan pratinjau publik. Ini bukan uji penerimaan merchant nyata atau audit visual manual seluruh kondisi.

**Admin — pemeriksaan pesanan:** daftar browser mencari nomor atau nama pemesan (bukan nama produk), menapis status/tanggal serah, memisahkan hapus pencarian dan reset filter. Rincian memakai item pesanan tersimpan, menampilkan saldo terpisah dari pokok pembayaran, dan membawa konteks ke daftar asal. Jangan mengaktifkan endpoint transisi generik; tindakan uang tetap berlangsung di antrean khusus. Respons JSON tetap untuk klien API.

**Admin — permohonan pembatalan:** tampilkan antrean menunggu, alasan pelanggan dan tautan rincian dengan tujuan kembali. Setujui dan Tolak adalah dua formulir berbeda, masing-masing mengikat nomor pesanan dan catatan keputusan. Persetujuan menentukan kewajiban refund, tidak mencatat uang keluar. Penolakan tidak mengubah status pesanan.

**Admin — rekonsiliasi bank:** pisahkan mutasi belum dikreditkan dari pengembalian yang sudah dicatat. Verifikasi nominal masuk penuh dan referensi keluar aktual; jangan memulai transfer dan jangan menyebut pengembalian mutasi sebagai pelunasan atau refund pembatalan. Setelah respons jaringan tidak pasti, periksa antrean/riwayat melalui GET, bukan kirim POST ulang otomatis.

**Admin — audit kas antar:** dari baris setoran, buka riwayat baca-saja untuk melihat kasir, waktu, perubahan dan alasan koreksi. Kembali ke tanggal, filter petugas, halaman dan baris asal. Saldo setoran tidak menambah pembayaran pelanggan.

**Admin — meja pemeriksaan:** navigasi langsung ke konfirmasi bank lintas tanggal atau setoran per tanggal WIB. Dari ringkasan, batasi setoran ke satu petugas; melepas filter mempertahankan tanggal dan halaman antrean bank. Chrome menelusuri filter → hitung fisik → riwayat → kembali → semua petugas.

**Admin — refund pembatalan:** rincian pesanan membedakan keputusan bernilai nol, kewajiban menunggu pencatatan dan transfer keluar terverifikasi. Hanya kewajiban menunggu yang membuka formulir ledger; keputusan tidak mengirim uang. Setelah tercatat, tampilkan referensi dan waktu verifikasi tanpa tombol kirim ulang.

**Admin — rincian personalisasi:** Admin dan pelanggan membaca snapshot item/opsi dan komponen biaya yang sama; jangan hitung ulang memakai katalog terkini. Chrome melacak pesanan Pita/Perak dan Kartu pesan dari checkout hingga pemeriksaan operasional.

**Admin — pemeriksaan papan dan riwayat:** navigasi bagian rincian, teks sesuai metode/jadwal, edit tiga baris hanya sebelum produksi, deteksi teks kedaluwarsa saat simpan. Riwayat status baca-saja menampilkan tahap lama/baru, pelaku, waktu dan catatan terbaru lebih dahulu.

**Admin — unit sewa per item:** tampilkan kode fisik dan riwayat penggantian, filter calon pengganti menurut varian, kondisi, penguasaan serta reservasi/blokir pada tanggal serah. Penggantian memakai transaksi lama yang memeriksa ulang stok, tahap dan pembatalan; tidak mengubah harga.

**Admin — durasi sewa:** tampilkan batas kembali WIB, sisa kuota dan biaya tiap pilihan sebelum menyimpan. Riwayat menyimpan alasan, biaya dan batas baru. Tolak formulir dengan batas lama; replay identik tidak menambah saldo lagi.

**Admin — bukti inspeksi per unit:** bandingkan serah/kembali berdampingan bila ruang cukup. Foto hanya dibuka melalui rute privat pesanan; tahap kosong tidak berarti kondisi baik. Inspeksi tidak otomatis menetapkan tarif atau memotong deposit.

**Admin — setelah penilaian retur:** tampilkan tarif yang dibekukan, saldo dan posisi deposit. Pisahkan penerimaan setoran fisik dari pemakaian deposit oleh Owner. Formulir penutupan hanya setelah unit kembali, saldo nol dan konfirmasi setoran jika tunai; minta hasil aktual dan catatan, bukan mengisi bukti pengembalian secara otomatis.

**Penilaian retur:** setelah semua inspeksi kembali lengkap, Admin dapat menetapkan Rp0 untuk kondisi baik. Kondisi selain baik memerlukan Owner. Estimasi jumlah × tarif membantu peninjauan; server menghitung tarif saat transaksi dan menolak nominal konfirmasi berbeda. Keputusan tidak memotong deposit.

**Retur — pemakaian deposit oleh Owner:** pisahkan aksi finansial sekali pakai dari konfirmasi setoran oleh Admin. Tampilkan plafon, estimasi saldo dan sisa deposit sebelum konfirmasi; gunakan kriteria tagihan yang sama pada GET dan transaksi terkunci. Sesudah keputusan, hanya jejak nominal, alasan, waktu dan petugas yang tampil.

**Regresi Chrome retur:** mulai dari fixture inspeksi kembali sintetis. Periksa izin Admin/Owner, estimasi dan konfirmasi tarif, setoran fisik, proyeksi deposit/saldo dan hasil sesudah simpan. Nominal konfirmasi tidak cocok dicegah di browser serta divalidasi kembali di server.

**Admin — keputusan rekonsiliasi bank:** bedakan keputusan selisih kode unik, reaktivasi nyata dengan pemeriksaan stok, dan bukti transfer keluar. Kelompok dikreditkan setelah pemeriksaan hanya memuat selisih yang diterima dan reaktivasi sukses, bukan semua transfer bank biasa.

**Admin — telusur mutasi:** pencarian literal dalam kelompok menurut nomor pesanan/referensi masuk, serta referensi keluar untuk pengembalian. Perpindahan kelompok mempertahankan pencarian; menghapus pencarian mempertahankan kelompok. Chrome menelusuri satu transfer tambahan hingga bukti keluar tanpa kredit ganda.

**Gerbang Admin:** lihat [audit SRS dan celah akses Admin](audit-admin.md); cakupan layar lintas ukuran tidak menutup celah UC-21/22/24.

**Staf Produksi — antrean:** masuk sebagai staf_produksi, saring nomor/tanggal serah/tahap, periksa rincian item dan sunting teks papan pada pesanan dikonfirmasi. Simpan mengembalikan konteks antrean; revisi basi ditolak dan teks terkunci setelah produksi dimulai. Chrome menguji alur siap jual → siap dengan akses Admin ditolak.

**Produksi → Kurir:** konfirmasi tiga baris papan sebelum T05. Formulir membawa versi yang dibaca; bila teks berubah di tab lain, tolak mulai produksi dalam transaksi dan minta pemeriksaan ulang. Chrome mengedit papan, menguji konflik, mengunci, menandai siap dan menemukan tugas antar bertanggal/jam sama dalam sesi Kurir.

**Kurir — inspeksi sewa:** kartu agenda memuat checklist setiap unit, foto privat 1–6 per tahap, kondisi/catatan, pemeriksaan identitas tanpa unggah identitas atau deposit tunai. Saat jemput jangan ulangi penerimaan jaminan. Chrome menguji jadwal, penyerahan dan jemput melalui akun Kurir.

**Kurir — kamera dan jaringan:** satu pilihan kamera ditambahkan ke daftar berkas milik unit, dapat dihapus sebelum submit; tidak menyimpan draf foto di browser. Bila server menolak ukuran (413), koreksi berkas tanpa kehilangan checklist. Bila respons hilang, kunci kirim dan periksa agenda lewat GET sebelum mencoba tindakan lain. Chrome mensimulasikan input kamera dan respons hilang setelah commit; bukan uji kamera fisik.

**Kurir — navigasi lapangan:** cari nomor di dalam tanggal/jenis tugas, pertahankan nomor saat pindah tab; kontak WhatsApp membuka draf, bukan pesan otomatis. Tampilkan tanggal dan jam batas jemput. [Lembar uji perangkat dan jaringan nyata](penerimaan-kurir.md) dipisahkan dari bukti Chrome sintetis.

Lihat [matriks audit halaman dan batas bukti per peran](matriks-ui-ux.md) sebelum menyatakan UI/UX selesai.

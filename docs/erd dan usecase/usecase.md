# Use Case Specification - SIBERKAH

**Sumber:** SRS SIBERKAH v1.1 (Bab 5.3)
**Total Use Case:** 28
**Aktor:** 4 (Pelanggan, Owner, Admin, Staf Produksi/Kurir)

---

## Daftar Isi
- [UC-01 Lihat Katalog](#uc-01-lihat-katalog)
- [UC-02 Cari Berdasarkan Tanggal](#uc-02-cari-berdasarkan-tanggal)
- [UC-03 Masuk dengan OTP](#uc-03-masuk-dengan-otp)
- [UC-04 Keluar](#uc-04-keluar)
- [UC-05 Buat Pesanan](#uc-05-buat-pesanan)
- [UC-06 Isi Pilihan dan Ucapan](#uc-06-isi-pilihan-dan-ucapan)
- [UC-07 Bayar Pesanan](#uc-07-bayar-pesanan)
- [UC-08 Lacak Pesanan](#uc-08-lacak-pesanan)
- [UC-09 Hubungi Toko lewat WhatsApp](#uc-09-hubungi-toko-lewat-whatsapp)
- [UC-10 Masuk Staf](#uc-10-masuk-staf)
- [UC-11 Verifikasi Pembayaran](#uc-11-verifikasi-pembayaran)
- [UC-12 Serah Terima Barang](#uc-12-serah-terima-barang)
- [UC-13 Terima Pengembalian](#uc-13-terima-pengembalian)
- [UC-14 Kelola Katalog](#uc-14-kelola-katalog)
- [UC-15 Kelola Pengaturan](#uc-15-kelola-pengaturan)
- [UC-16 Lihat Rekap](#uc-16-lihat-rekap)
- [UC-17 Batalkan Pesanan](#uc-17-batalkan-pesanan)
- [UC-18 Tangani Pembayaran Tidak Sesuai](#uc-18-tangani-pembayaran-tidak-sesuai)
- [UC-19 Catat Pelunasan dan Tunai](#uc-19-catat-pelunasan-dan-tunai)
- [UC-20 Catat Refund dan Jaminan](#uc-20-catat-refund-dan-jaminan)
- [UC-21 Input Pesanan Manual](#uc-21-input-pesanan-manual)
- [UC-22 Atur Blokir Tanggal dan Kapasitas](#uc-22-atur-blokir-tanggal-dan-kapasitas)
- [UC-23 Kelola Akun Staf](#uc-23-kelola-akun-staf)
- [UC-24 Kelola Unit Sewa](#uc-24-kelola-unit-sewa)
- [UC-25 Lihat Agenda Harian](#uc-25-lihat-agenda-harian)
- [UC-26 Lihat Riwayat Pesanan](#uc-26-lihat-riwayat-pesanan)
- [UC-27 Kelola Profil dan Hapus Akun](#uc-27-kelola-profil-dan-hapus-akun)
- [UC-28 Aktifkan Kembali Pesanan Kedaluwarsa](#uc-28-aktifkan-kembali-pesanan-kedaluwarsa)

---

## Detail Use Case

---

### UC-01 Lihat Katalog
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pengunjung melihat daftar produk tanpa perlu login |
| **Aktor Utama** | Pelanggan, Pengunjung |
| **Precondition** | Tidak ada |
| **Main Flow** | 1. Pengunjung membuka beranda/halaman katalog<br>2. Pengunjung memilih kategori produk<br>3. Pengunjung melihat detail produk (foto, deskripsi, harga, varian, opsi) |
| **Postcondition** | Tidak ada data yang berubah |
| **Prioritas** | Must (KAT-01) |
| **Kriteria Penerimaan** | Beranda, daftar produk, dan detail produk bisa dibuka tanpa login. Hanya produk aktif yang tampil. (F) |
| **Jenis Uji** | F (Feature Test) |
| **Referensi SRS** | SCP-02, KAT-01, UC-01 |

---

### UC-02 Cari Berdasarkan Tanggal
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pengunjung mencari produk sewa yang tersedia pada tanggal tertentu |
| **Aktor Utama** | Pelanggan, Pengunjung |
| **Precondition** | Tidak ada |
| **Main Flow** | 1. Pengunjung mengisi tanggal acara<br>2. Sistem menampilkan varian dengan unit kosong pada tanggal tersebut<br>3. Jika penuh, sistem menyarankan 3 tanggal kosong terdekat dalam 14 hari |
| **Postcondition** | Tidak ada data yang berubah |
| **Prioritas** | Must (KAT-02) |
| **Kriteria Penerimaan** | Hasil hanya memuat varian yang punya minimal satu unit kosong pada tanggal itu. Waktu respons ≤500ms untuk 200 produk dan 5.000 baris reservasi. (P) |
| **Jenis Uji** | P (Performance Test) |
| **Referensi SRS** | SCP-02, KAT-02, KAT-05, UC-02 |

---

### UC-03 Masuk dengan OTP
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan masuk memakai nomor HP dan kode OTP 6 digit |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Pelanggan punya nomor HP aktif |
| **Main Flow** | 1. Pelanggan menekan Masuk saat akan memesan<br>2. Pelanggan mengisi nomor HP (format 628xx)<br>3. Sistem mengirim kode OTP 6 digit lewat penyedia<br>4. Pelanggan memasukkan kode<br>5. Sistem memeriksa: kode benar, belum kedaluwarsa (5 menit), belum dipakai, max 5 percobaan<br>6. Jika nomor baru: sistem membuat akun, pelanggan mengisi nama dan menyetujui Kebijakan Privasi<br>7. Pelanggan kembali ke halaman asal |
| **Alternative Flow** | Kode salah: sisa percobaan ditampilkan. Setelah 5x: kode hangus.<br>Kode kedaluwarsa: minta kode baru setelah jeda 60 detik.<br>Permintaan terlalu sering: rate limit 3/15 menit per nomor & IP.<br>Gagal kirim: tombol hubungi Admin via WhatsApp. |
| **Postcondition** | Pelanggan berhasil masuk, hanya melihat menu sesuai perannya |
| **Prioritas** | Must (AUTH-01, AUTH-02, AUTH-03, AUTH-05, AUTH-07, AUTH-08, AUTH-09) |
| **Kriteria Penerimaan** | Nomor diubah ke format 628, satu nomor satu akun. Kode 6 digit hash, berlaku 5 menit, sekali pakai, hangus setelah 5x salah. Rate limit 3/15 menit. Sesi valid, logout berfungsi. Peran terpisah. (U, F) |
| **Jenis Uji** | U, F |
| **Referensi SRS** | SCP-01, AUTH-01 sampai AUTH-11, UC-03 |

---

### UC-04 Keluar
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pengguna mengakhiri sesi |
| **Aktor Utama** | Pelanggan, Staf |
| **Precondition** | Sudah login |
| **Main Flow** | 1. Pengguna menekan Keluar<br>2. Sistem menghapus sesi<br>3. Halaman yang butuh login tidak bisa dibuka lagi |
| **Postcondition** | Sesi berakhir |
| **Prioritas** | Must (AUTH-05) |
| **Kriteria Penerimaan** | Setelah keluar, halaman butuh login tidak bisa dibuka. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-01, AUTH-05, UC-04 |

---

### UC-05 Buat Pesanan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan memesan produk, sistem mengunci unit & membuat tagihan |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Pelanggan sudah login, produk tersedia |
| **Main Flow** | 1. Pelanggan memilih tanggal, varian, opsi, jumlah<br>2. Pelanggan mengisi teks papan (judul 25, penerima 45, pengirim 40)<br>3. Pelanggan memilih cara serah (antar/ambil), alamat, zona, jam<br>4. Sistem menghitung harga server-side, tampilkan ringkasan<br>5. Total ≥ 250k: pilih DP 50% atau Lunas. < 250k: wajib Lunas<br>6. Pelanggan setuju Syarat Sewa, tekan Buat Pesanan<br>7. **Transaksi DB**: cek kapasitas, lock unit (SELECT FOR UPDATE), insert hari_reservasi (unique unit+tanggal), buat pesanan+item+opsi, generate nomor BK-YYMM-NNNN, buat tagihan + kode unik 1-999, set tahan_hingga_pada (60/30 menit) |
| **Alternative Flow** | Unit terakhir diambil orang lain: tampil "Tanggal baru saja terisi", saran tanggal lain.<br>Stok habis/kapasitas penuh: pesanan ditolak.<br>Pesan setelah 15:00 WIB: tanggal paling awal bergeser 1 hari.<br>Alamat di luar zona: ditolak, saran hubungi toko. |
| **Postcondition** | Pesanan status Menunggu Pembayaran, unit tertahan sampai batas waktu |
| **Prioritas** | Must (PSN-01 sampai PSN-10, PSN-13, PSN-14, PSN-15, STK-01) |
| **Kriteria Penerimaan** | 5 langkah setelah login. 50 request bersamaan unit terakhir → 1 berhasil. Penahanan 60/30 menit, expired release ≤2 menit. Harga dari server, salin harga. Nomor BK-YYMM-NNNN unique 50 concurrent. (K, U, F) |
| **Jenis Uji** | K, U, F |
| **Referensi SRS** | SCP-03, PSN-01 sampai PSN-15, STK-01, UC-05, UC-06 |

---

### UC-06 Isi Pilihan dan Ucapan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan memilih varian, opsi, menulis teks papan dengan pratinjau live |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Sudah login, produk terpilih tersedia |
| **Main Flow** | 1. Pilih varian (ukuran, warna)<br>2. Pilih opsi (pilihan, teks bebas, per satuan)<br>3. Isi teks papan 3 kolom (judul ≤25, penerima ≤45, pengirim ≤40)<br>4. Pratinjau teks berubah real-time (debounce 200ms)<br>5. Pilih cara serah: antar (alamat, peta, zona) atau ambil (hanya buket) |
| **Postcondition** | Data siap dikirim ke PlaceOrder |
| **Prioritas** | Must (PSN-05, PSN-06, PSN-08) |
| **Kriteria Penerimaan** | Teks 3 kolom dengan batas karakter. Pratinjau update ≤200ms. Cara serah mengikuti jenis produk. (F) |
| **Jenis Uji** | F, P |
| **Referensi SRS** | SCP-03, PSN-05, PSN-06, PSN-08, UC-06 |

---

### UC-07 Bayar Pesanan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan transfer sesuai nominal + kode unik, kirim konfirmasi via WhatsApp |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Pesanan status Menunggu Pembayaran |
| **Main Flow** | 1. Pelanggan lihat nominal tagihan + kode unik 3 digit (1-999)<br>2. Pelanggan lihat rekening bank (BCA/BRI/Mandiri/Jambi) & QRIS statis<br>3. Pelanggan transfer tepat nominal, tekan "Kirim konfirmasi lewat WhatsApp"<br>4. WA terbuka dengan pesan terisi (nomor pesanan, nominal)<br>5. Admin cek mutasi & verifikasi manual |
| **Alternative Flow** | Batas waktu habis: status → Kedaluwarsa, unit dilepas.<br>Bayar terlambat tapi tanggal kosong: T13 (aktif kembali).<br>Bayar terlambat tanggal sudah terpakai: T14 (dana dikembalikan). |
| **Postcondition** | Status pembayaran menunggu verifikasi, hitung mundur berjalan |
| **Prioritas** | Must (BYR-01, BYR-02, BYR-04, BYR-06, BYR-07, BYR-08, BYR-09, BYR-10, BYR-11) |
| **Kriteria Penerimaan** | Tagihan + kode unik 1-999. Tidak ada 2 pembayaran menunggu dengan nominal sama. Verifikasi manual oleh Admin/Owner. Nominal harus sama persis untuk diterima. (U, F) |
| **Jenis Uji** | U, F |
| **Referensi SRS** | SCP-04, BYR-01 sampai BYR-11, UC-07 |

---

### UC-08 Lacak Pesanan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan memantau status pesanan via timeline |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Sudah login, punya minimal 1 pesanan |
| **Main Flow** | 1. Buka "Pesanan Saya" → daftar pesanan<br>2. Pilih pesanan → lihat timeline status<br>3. Status yang tampil: Menunggu Pembayaran → Dikonfirmasi → (Diproduksi) → Siap → Diserahkan → (Dikembalikan) → Selesai<br>4. Tombol sesuai status: Bayar, Batalkan, Ajukan Pembatalan, Hubungi WA<br>5. Auto-refresh 60 detik saat halaman terbuka |
| **Alternative Flow** | Buka pesanan orang lain → 404. Belum ada pesanan → ajak ke katalog. |
| **Postcondition** | Tidak ada data berubah |
| **Prioritas** | Must (AKN-01) |
| **Kriteria Penerimaan** | Daftar & detail pesanan sendiri dengan timeline. Detail refresh tiap 60 detik. Pesanan orang lain → 404. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-05, AKN-01, UC-08 |

---

### UC-09 Hubungi Toko lewat WhatsApp
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Membuka WhatsApp dengan pesan terisi otomatis |
| **Aktor Utama** | Pelanggan, Admin |
| **Precondition** | Sudah login, punya pesanan (pelanggan) atau buka detail pesanan (admin) |
| **Main Flow** | 1. Pelanggan/Admin menekan "Hubungi Toko"<br>2. WA terbuka via wa.me dengan pesan terisi (nomor pesanan, status, nama)<br>3. Pengguna tinggal kirim |
| **Postcondition** | Tidak ada data berubah |
| **Prioritas** | Must (NTF-02) |
| **Kriteria Penerimaan** | Link wa.me dengan pesan terisi ≤1000 karakter. Tidak ada pengiriman otomatis. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-05, NTF-02, UC-09 |

---

### UC-10 Masuk Staf
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Staf masuk dengan email & password |
| **Aktor Utama** | Owner, Admin, Staf |
| **Precondition** | Akun staf sudah dibuat |
| **Main Flow** | 1. Buka halaman masuk staf, isi email & password<br>2. Sistem cek kredensial. Login pertama: wajib ganti password<br>3. Arahkan ke dasbor sesuai peran |
| **Alternative Flow** | Sandi salah: pesan umum "Email atau sandi tidak sesuai".<br>Rate limit: 5 percobaan/menit. |
| **Postcondition** | Staf masuk ke dasbor perannya |
| **Prioritas** | Must (AUTH-08) |
| **Kriteria Penerimaan** | Sandi minimal 10 karakter, max 5 percobaan/menit, session idle 120 menit, wajib ganti sandi login pertama, tidak ada akun bawaan. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-01, AUTH-08, UC-10 |

---

### UC-11 Verifikasi Pembayaran
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin mencocokkan mutasi rekening dengan tagihan & memverifikasi |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Ada pembayaran status Menunggu |
| **Main Flow** | 1. Admin buka daftar verifikasi → pilih pembayaran<br>2. Detail: tampilkan tagihan, kode unik, nominal diharapkan<br>3. Input nominal diterima (wajib), pilih rekening masuk<br>4. Jika nominal sama persis → Terima (aktif), status → Dikonfirmasi<br>5. Jika beda → Tolak (alasan wajib) ATAU Terima dengan catatan<br>6. Log riwayat status & pembayaran |
| **Postcondition** | Status pembayaran & pesanan tercatat dengan pelaku & waktu |
| **Prioritas** | Must (BYR-02, BYR-04, BYR-07, BYR-16) |
| **Kriteria Penerimaan** | Hanya Admin/Owner. Nominal harus sama persis → Terima. Beda → Tolak/ catatan. Tidak pernah verifikasi otomatis nominal beda. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-04, BYR-01 sampai BYR-11, UC-11 |

---

### UC-12 Serah Terima Barang
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Kurir menyerahkan barang sewa, inspeksi & foto |
| **Aktor Utama** | Staf Kurir |
| **Precondition** | Pesanan status Siap, saldo = 0 |
| **Main Flow** | 1. Kurir buka agenda antar<br>2. Isi daftar periksa inspeksi_serah: permukaan akrilik, kaki/easel, rangkaian bunga, lampu LED<br>3. Upload foto 1-6<br>4. Cek saldo = 0 (BR-26)<br>5. Jaminan: catat jenis & nominal (BR-12). KTP/KTM tidak diunggah, staf catat "diperiksa oleh [nama]"<br>6. Status → Sudah Diserahkan (T08)<br>7. Log riwayat status |
| **Alternative Flow** | Saldo ≠ 0: sistem tolak perubahan status. |
| **Postcondition** | Status = Sudah Diserahkan, inspeksi & foto tersimpan |
| **Prioritas** | Must (OPS-02, OPS-08) |
| **Kriteria Penerimaan** | Checklist + foto 1-6. Saldo wajib nol. Jaminan tercatat. Log status. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-06, OPS-01 sampai OPS-10, UC-12 |

---

### UC-13 Terima Pengembalian
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Kurir menerima kembali barang sewa, inspeksi, denda, kerusakan |
| **Aktor Utama** | Staf Kurir |
| **Precondition** | Pesanan status Sudah Diserahkan, ada barang sewa |
| **Main Flow** | 1. Kurir buka agenda jemput<br>2. Inspeksi kembali: daftar periksa + foto 1-6<br>3. Hitung keterlambatan: jam telat × Rp25.000 (max tarif harian, BR-10)<br>4. Cek kerusakan: pilih tarif_kerusakan (Akrilik Rp200k, Bunga Rp30k/tangkai, Easel Rp100k, BR-11). Owner putuskan nominal final<br>4. Catat penyesuaian_pesanan: denda + kerusakan (ADJ-01)<br>5. Jaminan: dikembalikan ATAU disita (BR-12)<br>6. Status → Sudah Dikembalikan (T09)<br>7. Log riwayat status |
| **Postcondition** | Status = Sudah Dikembalikan, denda/kerusakan tercatat |
| **Prioritas** | Must (OPS-02, OPS-09) |
| **Kriteria Penerimaan** | Denda = jam × 25k (max tarif harian). Kerusakan pakai tarif acuan. Jaminan dikembalikan/disita. Log status. (U, F) |
| **Jenis Uji** | U, F |
| **Referensi SRS** | SCP-06, BR-10, BR-11, BR-12, UC-13 |

---

### UC-14 Kelola Katalog
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner CRUD produk, varian, opsi, foto |
| **Aktor Utama** | Owner |
| **Precondition** | Sudah login sebagai Owner |
| **Main Flow** | 1. Owner buka Katalog → Tambah produk: nama, kategori, jenis (sewa/jual/custom), deskripsi, harga dasar, lead time, izin hari sama, cara serah<br>2. Tambah varian & opsi<br>3. Upload foto: JPG/PNG/WebP, max 5MB, min 800px width. Auto WebP, EXIF strip. Alt text wajib<br>4. Simpan sebagai draf, lalu Terbitkan<br>5. Produk pernah dipesan → tidak bisa dihapus, hanya diarsipkan |
| **Alternative Flow** | Foto bukan JPG/PNG/WebP, >5MB, <800px → ditolak. |
| **Postcondition** | Perubahan tampil di katalog, tercatat di log aktivitas |
| **Prioritas** | Must (KTL-01, KTL-02, KTL-03, KTL-04, AUD-01) |
| **Kriteria Penerimaan** | CRUD produk + varian + opsi. Harga baru tidak ubah pesanan lama. Foto WebP, EXIF strip, alt wajib. Produk lama diarsipkan, tidak dihapus. Log aktivitas tercatat. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-07, KTL-01 sampai KTL-05, AUD-01, UC-14 |

---

### UC-15 Kelola Pengaturan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner mengubah nilai bisnis tanpa ubah kode |
| **Aktor Utama** | Owner |
| **Precondition** | Sudah login sebagai Owner |
| **Main Flow** | 1. Owner buka Pengaturan<br>2. Ubah nilai: jam batas (15:00), ambang DP (250k), penahanan (60/30 menit), denda (25k/jam), tarif zona, tarif kerusakan, retensi data, dll<br>3. Setiap perubahan tercatat di log aktivitas (sebelum/sesudah) |
| **Postcondition** | Nilai bisnis terupdate, log tercatat |
| **Prioritas** | Must (SET-01) |
| **Kriteria Penerimaan** | Semua nilai bisnis diubah tanpa ubah kode. Setiap perubahan tercatat log (pelaku, waktu, sebelum, sesudah). (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-07, SET-01, AUD-01, UC-15 |

---

### UC-16 Lihat Rekap
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner melihat ringkasan pendapatan per periode |
| **Aktor Utama** | Owner |
| **Precondition** | Sudah login, ada pesanan |
| **Main Flow** | 1. Pilih periode: harian/mingguan/bulanan<br>2. Sistem tampil: jumlah pesanan, pendapatan terverifikasi, DP belum lunas, refund, denda, rincian per kategori, hari tersibuk<br>3. Unduh data anonim CSV (tanpa nama, WA, alamat, nomor pesanan → kode acak) |
| **Alternative Flow** | Tidak ada data pada periode → tampil "belum ada transaksi" |
| **Postcondition** | Tidak ada data berubah |
| **Prioritas** | Should (LAP-01, LAP-03) |
| **Kriteria Penerimaan** | Rekap hanya hitung pembayaran terverifikasi. Total = jumlah rincian. CSV anonim (tanpa PII). (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-08, LAP-01, LAP-03, UC-16 |

---

### UC-17 Batalkan Pesanan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan membatalkan pesanan sendiri (status Menunggu Pembayaran) |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Pesanan status Menunggu Pembayaran |
| **Main Flow** | 1. Pelanggan buka Pesanan Saya → pilih pesanan<br>2. Tekan "Batalkan Pesanan"<br>3. Sistem: status → Dibatalkan (T04), unit dilepas, tidak ada refund (belum ada pembayaran terverifikasi) |
| **Alternative Flow** | Status sudah Dikonfirmasi: hanya bisa "Ajukan Pembatalan" (butuh approval Admin/Owner). |
| **Postcondition** | Pesanan dibatalkan, unit dilepas |
| **Prioritas** | Must (PSN-13, BR-14) |
| **Kriteria Penerimaan** | Batalkan sendiri hanya saat Menunggu Pembayaran. Setelahnya hanya Ajukan Pembatalan. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-03, PSN-13, PSN-14, BR-14, UC-17 |

---

### UC-18 Tangani Pembayaran Tidak Sesuai
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin menangani nominal bayar beda dari tagihan |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Pembayaran status Menunggu, nominal beda |
| **Main Flow** | 1. Admin buka detail verifikasi<br>2. Nominal beda: pilih TOLAK (alasan wajib) ATAU TERIMA DENGAN CATATAN<br>3. TOLAK: status → Ditolak, catatan wajib, nominal_tertunda = NULL<br>4. TERIMA DENGAN CATATAN: status → Terverifikasi, catatan wajib, selisih jadi penyesuaian_pesanan |
| **Postcondition** | Status pembayaran & pesanan terupdate, catatan wajib |
| **Prioritas** | Must (BYR-07, BYR-16, BR-16) |
| **Kriteria Penerimaan** | Nominal beda tidak pernah diverifikasi otomatis. Admin wajib pilih Tolak (alasan) atau Terima dengan catatan. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-04, BYR-07, BYR-16, BR-16, UC-18 |

---

### UC-19 Catat Pelunasan dan Tunai
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin mencatat pelunasan DP & pembayaran tunai |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Pesanan status Dikonfirmasi, ada sisa tagihan / pembayaran tunai |
| **Main Flow** | 1. Admin buka catat pelunasan<br>2. Input nominal diterima, pilih rekening masuk<br>3. Verifikasi nominal sama persis (wajib sama persis)<br>4. Status → Terverifikasi, update saldo pesanan<br>5. Tunai: hanya untuk beli langsung produk siap jual di toko & deposit jaminan dari kurir |
| **Postcondition** | Tagihan pelunasan terverifikasi, saldo update |
| **Prioritas** | Must (BYR-06, BYR-08) |
| **Kriteria Penerimaan** | Nominal harus sama persis. Tunai hanya beli langsung siap jual + deposit jaminan kurir. Deposit: ditahan → dikembalikan/disita. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-04, BYR-06, BYR-08, UC-19 |

---

### UC-20 Catat Refund dan Jaminan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin mencatat refund & status deposit jaminan |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Ada pembatalan setelah pembayaran terverifikasi / jaminan dikembalikan |
| **Main Flow** | 1. Sistem hitung refund otomatis (BR-13): persen_waktu (H-2+=100%, H-1=50%, H=0%) × persen_status (Dikonfirmasi=100%, Diproduksi=50%, Siap=0%) → MIN<br>2. Admin CATAT transfer refund (sistem tidak kirim uang)<br>3. Catat penyesuaian_pesanan: jenis=refund, nominal negatif<br>4. Owner SAJA boleh ubah nominal refund final<br>5. Jaminan: status ditahan → dikembalikan ATAU disita (jadi kerusakan) |
| **Postcondition** | Refund & jaminan tercatat |
| **Prioritas** | Must (BYR-09, BR-13) |
| **Kriteria Penerimaan** | Refund = MIN(persen_waktu, persen_status). Admin catat transfer. Sistem tidak kirim uang. Owner saja ubah nominal final. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-04, BYR-09, BR-13, UC-20 |

---

### UC-21 Input Pesanan Manual
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin memasukkan pesanan dari WhatsApp/toko |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Sudah login, akses Admin/Owner |
| **Main Flow** | 1. Admin buka form pesanan manual<br>2. Isi data seperti pelanggan (nama, nomor WA, produk, varian, tanggal, opsi, teks, cara serah, alamat, zona, jam)<br>3. Aturan SAMA seperti pesanan web<br>4. Pengecualian: wajib alasan, hanya Admin/Owner<br>5. asal = 'admin', pengguna_id = admin yang input |
| **Alternative Flow** | Pengecualian tanpa alasan → ditolak sistem |
| **Postcondition** | Pesanan manual masuk dengan aturan yang sama |
| **Prioritas** | Must (OPS-05, BR-20) |
| **Kriteria Penerimaan** | Aturan sama pesanan web. Pengecualian wajib alasan + hanya Admin/Owner. asal='admin'. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-06, OPS-05, BR-20, UC-21 |

---

### UC-22 Atur Blokir Tanggal dan Kapasitas
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner/Admin menutup tanggal toko, memblokir unit, set kapasitas harian |
| **Aktor Utama** | Owner, Admin |
| **Precondition** | Sudah login Owner/Admin |
| **Main Flow** | 1. Owner buka Kalender Blokir<br>2. Tutup tanggal toko (produk_id kosong = seluruh toko) atau blokir unit per tanggal<br>3. Set kapasitas produksi harian per kelompok_kapasitas (default buket custom 15/hari)<br>4. Cek pesanan aktif yang bentrok → PERINGATAN saja, tidak batalin otomatis |
| **Alternative Flow** | Blokir tanggal yang sudah punya pesanan aktif → peringatan, tidak batalin |
| **Postcondition** | Blokir & kapasitas tercatat, pesanan aktif diberi peringatan |
| **Prioritas** | Must (OPS-06, BR-21) |
| **Kriteria Penerimaan** | Blokir tanggal/unit. Kapasitas harian per kelompok. Pesanan aktif bentrok → peringatan saja. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-06, OPS-06, BR-21, UC-22 |

---

### UC-23 Kelola Akun Staf
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner meminta pembuatan/penonaktifan akun staf via pengembang |
| **Aktor Utama** | Owner (minta), Pengembang (jalankan) |
| **Precondition** | Owner butuh akun staf baru / nonaktifkan |
| **Main Flow** | 1. Owner minta ke pengembang<br>2. Pengembang jalanin artisan command: `php artisan staff:create` / `staff:deactivate`<br>3. Akun dibuat dengan peran & email, password di-set ulang saat login pertama |
| **Postcondition** | Akun staf aktif/nonaktif |
| **Prioritas** | Should (AKN-02) |
| **Kriteria Penerimaan** | Akun staf dibuat/dinonaktifkan via artisan oleh pengembang atas permintaan Owner. Halaman kelola akun belum ada v1. (M) |
| **Jenis Uji** | M |
| **Referensi SRS** | SCP-01, AKN-02, UC-23 |

---

### UC-24 Kelola Unit Sewa
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Owner/Admin CRUD unit sewa (kode unik, status aktif/perawatan/rusak/pensiun) |
| **Aktor Utama** | Owner, Admin |
| **Precondition** | Sudah login Owner/Admin |
| **Main Flow** | 1. Buka Unit Sewa<br>2. Tambah unit: kode unik (misal AKR-001), varian, status aktif<br>3. Nonaktifkan unit rusak (status perawatan/rusak/pensiun) → tidak dihitung ketersediaan<br>4. Unit nonaktif tidak bisa dipesan |
| **Postcondition** | Unit sewa terupdate, ketersediaan otomatis berubah |
| **Prioritas** | Must (KTL-02) |
| **Kriteria Penerimaan** | Setiap varian sewa punya unit berkode unik. Unit nonaktif tidak dihitung ketersediaan. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-07, KTL-02, UC-24 |

---

### UC-25 Lihat Agenda Harian
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Staf/Admin melihat agenda antar, jemput, produksi hari ini |
| **Aktor Utama** | Staf, Admin, Owner |
| **Precondition** | Sudah login |
| **Main Flow** | 1. Buka "Hari Ini" / Agenda<br>2. Lihat cards per tugas: antar (jam, alamat, peta, no HP), jemput, produksi (antrean rangkai by tanggal acara), verifikasi<br>3. Peringatan: unit belum kembali tapi dipesan besok<br>4. Urutkan menurut jam |
| **Postcondition** | Tidak ada data berubah |
| **Prioritas** | Should (OPS-03, OPS-04, OPS-07) |
| **Kriteria Penerimaan** | Agenda urut jam, alamat, peta, no HP. Peringatan unit belum kembali. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-06, OPS-03, OPS-04, OPS-07, UC-25 |

---

### UC-26 Lihat Riwayat Pesanan
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan melihat seluruh pesanan lama |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Sudah login, punya pesanan |
| **Main Flow** | 1. Buka "Pesanan Saya" → daftar semua pesanan<br>2. Pilih pesanan → detail lengkap (timeline, rincian, pembayaran, jadwal) |
| **Postcondition** | Tidak ada data berubah |
| **Prioritas** | Must (AKN-01) |
| **Kriteria Penerimaan** | Daftar & detail riwayat pesanan sendiri. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-05, AKN-01, UC-26 |

---

### UC-27 Kelola Profil dan Hapus Akun
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Pelanggan ubah nama atau anonimkan akun |
| **Aktor Utama** | Pelanggan |
| **Precondition** | Sudah login |
| **Main Flow** | 1. Buka Profil → ubah nama<br>2. Hapus akun: anonimisasi (nama → "Pelanggan Dihapus", nomor_wa → hash). Pesanan & pembayaran tetap utuh untuk pembukuan<br>3. Penghapusan ditolak jika masih ada pesanan aktif |
| **Postcondition** | Data pribadi dianonimkan, transaksi tetap ada |
| **Prioritas** | Should (AUTH-06, AUTH-10) |
| **Kriteria Penerimaan** | Ubah nama bisa. Hapus akun → anonimisasi, transaksi tetap. Ditolak kalau ada pesanan aktif. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-01, AUTH-06, AUTH-10, BR-24, UC-27 |

---

### UC-28 Aktifkan Kembali Pesanan Kedaluwarsa
| Elemen | Detail |
|--------|--------|
| **Deskripsi** | Admin/Owner mengaktifkan pesanan kedaluwarsa jika bayar terlambat & tanggal masih kosong |
| **Aktor Utama** | Admin, Owner |
| **Precondition** | Pesanan status Kedaluwarsa, ada pembayaran terlambat masuk |
| **Main Flow** | 1. Admin cek: TANGGAL MASIH KOSONG?<br>2. YA → T13: Aktifkan kembali **DALAM TRANSAKSI**: lock unit ulang (INSERT hari_reservasi), status → Dikonfirmasi, reservasi permanen<br>3. TIDAK (sudah terambil) → T14: Tetap kedaluwarsa, dana dikembalikan PENUH, catat penyesuaian refund penuh<br>4. Semua lewat TransitionOrderStatus (satu pintu, BR-25) |
| **Postcondition** | Pesanan aktif kembali ATAU dana dikembalikan penuh |
| **Prioritas** | Should (PSN-07, BR-15, T13, T14) |
| **Kriteria Penerimaan** | Tanggal kosong → T13 aktif kembali (lock unit dalam transaksi). Tanggal terambil → T14 dana dikembalikan penuh. Semua lewat TransitionOrderStatus. (F) |
| **Jenis Uji** | F |
| **Referensi SRS** | SCP-03, PSN-07, BR-15, T13, T14, UC-28 |

---

## Ringkasan Prioritas per Aktor

| Aktor | Must | Should | Could | Won't | Total |
|-------|------|--------|-------|-------|-------|
| Pelanggan | 8 | 2 | 0 | 0 | 10 |
| Owner | 5 | 3 | 0 | 0 | 8 |
| Admin | 6 | 1 | 0 | 0 | 7 |
| Staf | 2 | 3 | 0 | 0 | 5 |
| **Total UC** | **21** | **9** | **0** | **0** | **30** (beberapa UC dipakai multi-aktor) |

*Catatan: 28 UC unik, beberapa dipakai multi-aktor sehingga total mapping > 28*

---

## Mapping Use Case ke Modul (SCP)

| Modul (SCP) | Use Case |
|-------------|----------|
| SCP-01 Auth & Peran | UC-03, UC-04, UC-10, UC-23, UC-27 |
| SCP-02 Katalog & Cari Tanggal | UC-01, UC-02 |
| SCP-03 Pemesanan | UC-05, UC-06, UC-17, UC-28 |
| SCP-04 Pembayaran | UC-07, UC-11, UC-18, UC-19, UC-20 |
| SCP-05 Pelacakan & WhatsApp | UC-08, UC-09, UC-26 |
| SCP-06 Operasional Harian | UC-12, UC-13, UC-21, UC-22, UC-25 |
| SCP-07 Katalog & Pengaturan | UC-14, UC-15, UC-24 |
| SCP-08 Rekap & Laporan | UC-16 |

---

## Catatan Teknis untuk Pengerjaan

1. **Semua UC** memiliki acceptance criteria yang terukur di SRS
2. **Prioritas Must** = 21 UC (harus selesai sebelum 30 Nov 2026)
3. **Transisi status** (T01-T14) dikerjakan di Action `TransitionOrderStatus` (satu pintu)
3. **Concurrency** diuji di `PlaceOrder` (50 request unit terakhir), `OrderNumberGenerator` (50 concurrent), `ProductionCapacity` (20 request capacity 1)
4. **Security**: Setiap UC yang akses data punya ownership check (404 bukan 403)
5. **Audit**: Semua perubahan status via `TransitionOrderStatus` → write `riwayat_status_pesanan`

---

*File ini dibuat sebagai referensi detail untuk pengembangan Fase 3 ke depannya. Sumber utama tetap SRS.html.*
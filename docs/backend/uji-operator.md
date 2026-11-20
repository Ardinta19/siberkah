# Uji penerimaan lokal: buket, DP, tunai antar, dan setoran

**Batas aman.** Ini panduan untuk operator, bukan bukti penerimaan yang sudah ditandatangani. Gunakan checkout branch PR pada instalasi uji terpisah. Pastikan koneksi aplikasi menunjuk persis ke `siberkah_uji`, bukan basis data usaha, dan server hanya untuk operator (mis. `http://127.0.0.1:8011`). Periksa `php artisan about`, konfigurasi efektif dan `php artisan migrate:status` setelah membersihkan cache konfigurasi lokal. Cadangkan DB uji sebelum migrasi yang masih pending; jangan `migrate:fresh`, jangan mengubah jam laptop/tabel untuk melewati aturan bisnis. Jangan jalankan skrip CI di laptop. Migrasi/flag staging tidak boleh membuka rute produksi.

**Persiapan.** Konfirmasi `APP_ENV=local` atau `staging`, `RENTAL_CHECKOUT_STAGING=true`, versi privasi benar; gunakan akun pelanggan yang sudah OTP/onboard serta akun staf terpisah Admin, Kurir, Owner/kasir. Gunakan profil browser terpisah. Siapkan produk siap jual Rp130.000 dengan stok ≥2, zona Rp10.000 dan satu tujuan pembayaran uji aktif. Gunakan data sintetis saja, jangan transfer uang untuk pengujian ini. Pastikan ambang DP mengizinkan Rp270.000. Untuk memeriksa QRIS nyata, cocokkan nama merchant pada aplikasi pembayaran dalam sesi terpisah; jangan bagikan kode/nomor rekening di laporan.

### Sebelum mulai (PowerShell, laptop uji)

Dari checkout yang **sudah ada**, pastikan `git status -sb` menunjukkan branch `arena/b65c8b4e-siberkah` dan tidak ada perubahan lokal yang belum disimpan. Jika berbeda, berhenti; jangan reset atau clone ulang. Ambil pembaruan dengan `git pull --ff-only origin arena/b65c8b4e-siberkah`. Setelah konfigurasi lokal, migrasi dan data uji siap, jalankan:

```powershell
php artisan uji:operator
```

Perintah hanya membaca. Ia memeriksa lingkungan, nama DB efektif, daftar migrasi, peran aktif, produk/varian dan zona sesuai skenario, ambang DP, versi privasi, serta tujuan pembayaran aktif. Kode keluar bukan nol berarti **jangan mulai**; benahi prasyarat yang dilaporkan. Nama DB cocok bukan bukti bahwa host benar: operator harus memeriksa sendiri alamat host DB lokal. `OK` bukan bukti OTP bisa terkirim, stok masih tersedia saat checkout, atau rekening/merchant benar. Tidak ada akun atau data uji yang dibuat otomatis. Bila semuanya siap, jalankan `php artisan serve --host=127.0.0.1 --port=8011` dan buka `http://127.0.0.1:8011`.

| Pelaku | Aksi di aplikasi | Hasil yang harus dicatat |
|---|---|---|
| Pelanggan | Pilih 2 buket, antar, tanggal yang memenuhi tenggat DP, DP 50%, sisa tunai. Simpan nomor pesanan; buka tagihan lalu konfirmasikan simulasi pembayaran. | Total Rp270.000; nominal transfer simulasi **Rp135.000 + kode unik**. Belum lunas, jangan transfer lagi. |
| Admin | Di dasbor buka **Periksa pembayaran dan setoran antar**. Cocokkan tagihan dengan mutasi sintetis; masukkan nominal aktual termasuk kode dan referensi unik. | DP pokok Rp135.000, saldo Rp135.000. Kode unik bukan tambahan pendapatan. Tanpa bukti mutasi, jangan verifikasi. |
| Admin/Produksi | Buka `/staf/produksi`, pilih nomor yang sama, **Tandai siap**. | Siap, belum diserahkan; saldo tetap. |
| Kurir | Pada **tanggal serah sebenarnya**, buka `/staf/kurir`. Cocokkan barang dan pembeli; hitung Rp135.000, isi nominal diterima, centang konfirmasi, catat serah. | Dengan nominal salah, serah gagal tanpa kredit. Setelah berhasil: sudah diserahkan, saldo nol; jangan terima uang lagi bila halaman diulang. |
| Owner/kasir | Pada halaman pembayaran dan setoran, pilih **tanggal kurir menerima uang** (WIB). Cocokkan dengan nomor pesanan. Hitung kas, isi total kumulatif Rp135.000. | `belum_disetor` → `cocok`. Untuk skenario kurang lebih, tulis alasan, catat koreksi sebagai entri baru, jangan ubah pembayaran pelanggan. Penerima uang tidak boleh mengesahkan setoran sendiri. |
| Owner | Bandingkan `GET /admin/laporan/penerimaan?mulai=YYYY-MM-DD&akhir=YYYY-MM-DD` sebelum/sesudah setoran, mencakup hari DP dan hari serah. Gunakan selisih terhadap baseline bila ada transaksi lain. | Total pokok untuk skenario: bank Rp135.000 + tunai Rp135.000 = Rp270.000. Setoran kasir menambah **Rp0**. |

Catat commit, nomor pesanan uji, tanggal WIB, hasil tiap baris dan penyimpangan. Jangan sertakan OTP, sandi, foto identitas, cookie, atau referensi bank asli. Jika saldo, petugas, nominal, atau status berbeda: hentikan skenario, jangan catat pembayaran pengganti; simpan nomor pesanan dan tahap terakhir untuk rekonsiliasi. CI memakai tanggal simulasi terisolasi untuk menjalankan skenario lintas hari tanpa menunggu; uji lokal bersama operator tidak memakai fasilitas tersebut.

### Setelah kasir mencatat setoran

Dari terminal lain di checkout yang sama, ganti nomor contoh dengan nomor uji sebenarnya:

```powershell
php artisan uji:operator --pesanan=BK-2611-0001
```

Pemeriksaan mengambil satu snapshot transaksi: status pesanan, total/pokok bank dan tunai, mutasi DP persis termasuk kode, serta urutan saldo setoran sampai Rp135.000. Tidak menampilkan nama pelanggan, rekening, nomor WA, referensi mutasi, atau token. Ini khusus nilai dan alur contoh di atas: skenario lain bisa valid tetapi tidak akan lulus pemeriksaan ini. Catat hasilnya bersama perbandingan laporan dan hitungan kas fisik, bukan sebagai pengganti keduanya.

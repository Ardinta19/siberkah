# Standar antarmuka SIBERKAH

Acuan: `ui-ux/`. Terapkan menurut fungsi, bukan menyalin satu layout ke semua peran. Tinta wordmark publik `#8A052B` diambil dari piksel burgundy opak pada logo `public/images/berkah-kreatif.webp`.

| Situasi | Pola standar |
|---|---|
| Navigasi etalase | Navbar floating pill; ketika bergulir melewati intro, sisakan logo. Logo dapat membuka kembali navigasi. Jangan sembunyikan kontrol yang sedang difokuskan. |
| HP | Empat bottom tabs: Beranda, Katalog, Lacak, Akun. Hamburger membuka panel radial dengan label terbaca. Hormati safe area dan layar pendek. |
| Desktop | Navbar lengkap, Ctrl/Cmd+K untuk palet pencarian produk. Bukan pencarian halaman. |
| Cari dan saring | Satu formulir: nama produk saja, kategori aktif, jenis, tanggal sewa dan urutan. Bottom sheet di HP, dialog atas pada desktop. Simpan kondisi lewat query string; katalog hanya menampilkan hasil, ringkasan dan reset. Tanpa JS sediakan formulir native. |
| Navigasi daftar | Paginasi untuk navigasi biasa. Lebih dari empat halaman: jump page dengan batas numerik dan filter dipertahankan; popover berjangkar di desktop, inline di HP. |
| Menu kontekstual | Gunakan anchored popover untuk menu akun/aksi lokal, bukan dialog layar penuh. Kalender tetap dialog dengan keyboard dan pengembalian fokus. |
| Staf/Owner/Admin | Pertahankan sidebar peran dan aksi operasional. Tidak ada keranjang atau tab pelanggan di ruang kerja staf. |

## Gerak dan pengujian

Panel masuk dari pemicunya; sheet dari bawah, kalender bergeser sesuai arah bulan. Utamakan transform/opacity, jeda tipis antarbagian, dan animasi sekali ketika keadaan berubah. Angka pembayaran tidak melompat. Mode kurangi gerakan memakai perubahan warna/opacity tanpa memaksa zoom atau pergeseran besar. Jangan menjanjikan 60 fps tanpa pengukuran perangkat nyata.

Periksa 320–1440 px, keyboard, Escape, fokus kembali, ketukan berulang, mode kurangi gerakan, kombinasi pencarian+filter, dan keadaan kosong sebelum perubahan dinyatakan selesai.

React digunakan sebagai komponen interaktif kecil, bukan pengganti seluruh Blade. Ringkasan pencarian membaca keadaan form asli dan baru dimuat saat panel dibuka. Chip menghapus satu kriteria tanpa mengirim pesanan atau mengubah filter lain. Desktop menggunakan pantulan cahaya pada CTA saat hover; sentuhan merespons tekanan dan aktivasi, bukan sekadar pointer masuk. Jangan menganimasikan ulang setiap karakter pencarian atau memblokir navigasi demi memainkan efek.

### Default lintas layout: kaca, navigasi, dan tindakan independen
- Keempat layout (guest, customer, staff, admin termasuk owner) memakai shell pill yang merapat menuju logo, di desktop maupun ponsel. Area kerja dengan scroll internal mengamati penanda dalam konten, bukan posisi window. Logo membuka kembali kontrol.
- Gunakan liquid glass pada permukaan navigasi tetap: tepi cahaya, warna dasar pekat dan blur bila didukung. Form, tabel keuangan, dan konfirmasi tetap opak agar nilai mudah diperiksa.
- Desktop pelanggan mencari produk; ruang kerja mencari menu yang benar-benar dirender untuk perannya. Mobile memakai sheet; tab publik/pelanggan mengikuti empat tujuan belanja; tab kerja mengambil maksimal tiga tautan kerja, bukan tindakan logout. Sidebar kerja mempertahankan label dan fokus modal dengan susunan radial ringan.
- Hapus pencarian berdampingan dengan nama: hanya menghapus q. Reset filter berada di kelompok tindakan filter: pertahankan q. Di panel, keduanya menyunting draf sampai Cari produk ditekan. Di hasil katalog, tautan langsung menerapkan perubahan dan kembali ke halaman pertama.

**Kontrak keyboard:** panel produk dan panel menu kerja berbagi mekanisme tutup, klik latar, pemulihan fokus dan pintasan. Menahan pintasan tidak boleh membuka/menutup berulang atau menutup panel induk saat kalender aktif. Pada menu kerja, panah memilih tujuan, Enter membuka tautan, dan Tab tetap berfungsi secara alami. Pada panel produk, Enter tetap mengirim pencarian. Jump page memfokuskan nomor halaman; Escape kembali ke pemicu dan klik luar menutup tanpa mengirim.

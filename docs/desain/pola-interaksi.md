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

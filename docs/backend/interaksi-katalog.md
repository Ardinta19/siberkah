# Pemeriksaan kontrol etalase

Ruang lingkup: layout publik, beranda, katalog, detail, dan keranjang. Checkout, OTP, dashboard, dan halaman operator adalah tujuan navigasi, bukan bagian dari inventaris visual ini.

| Lokasi | Kontrol | Respons yang diharapkan |
|---|---|---|
| Header dan footer | Logo, menu utama, masuk/daftar, pesanan, keluar, kontak, layanan, media sosial, kebijakan | Tautan atau formulir asli; fokus keyboard terlihat. Tautan tidak menampilkan pesan sukses palsu. |
| HP | Menu hamburger; Beranda, Katalog, Lacak, Akun | Menu memberi tahu status terbuka dan bisa ditutup lewat Escape. Tab aktif diberi penanda. |
| Beranda | Jelajahi katalog, judul produk, jelajahi kategori | Menuju katalog, detail atau hasil kategori yang dipilih. |
| Katalog | Jelajahi produk, pencarian, Filter & urutan, jenis, tanggal, urutan, Cari produk | Tombol filter mencerminkan status terbuka. Tanggal tidak aktif untuk jual/custom. Setelah valid, Cari menampilkan status pencarian. |
| Hasil | Kategori, reset, keadaan kosong, paginasi | Navigasi asli. Paginasi mengikuti filter. Reset dan tautan keadaan kosong sengaja membuka semua produk. |
| Kartu | Foto, Lihat detail, favorit, ringkasan | Foto dan CTA menuju tujuan yang sama. Favorit memakai aria-pressed dan lencana tersimpan; ringkasan dibuka lewat summary asli. |
| Detail | Kembali, tanggal, Cek unit, saran tanggal | Kembali mempertahankan konteks katalog. Pemeriksaan tidak menahan unit; saran hanya ditampilkan ketika tersedia. Daftar varian adalah informasi, bukan tombol. |
| Detail | Jumlah, −/+, Simpan ke keranjang, Pesan produk ini/Masuk | Jumlah untuk keranjang, batas 1–100; tetap bisa diketik tanpa JS. Pesan langsung membuka formulir checkout terpisah. |
| Keranjang | Ikon dan jumlah item, nama produk, Perbarui, Hapus/Ya hapus, Lanjut pesan, kembali | Perbarui memberi status setelah respons server; Hapus meminta konfirmasi inline yang bisa ditutup; lanjut pesan membawa jumlah tersimpan. |

Uji browser CI meliputi viewport katalog, favorit, jumlah/konfirmasi keranjang, checkout. Untuk tinjauan visual manual: coba Tab/Enter/Escape, sentuhan yang berubah menjadi scroll, nama panjang, fokus pada halaman kedua, dan mode kurangi gerakan. Tidak semua kombinasi perangkat diuji otomatis.

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

**Kalender sewa (katalog dan detail):** sorotan hari dan tanggal yang sudah dipilih adalah dua keadaan berbeda. Panah menjelajahi hari/minggu; Home/End menuju Senin/Minggu; Page Up/Down berpindah bulan (dengan Shift: tahun). Jika tanggal 31 tidak ada di bulan tujuan, sorot hari terakhir yang valid. Hanya aktivasi tanggal atau tombol aksi yang mengubah nilai form. Escape kembali ke pemicu tanpa mengirim pencarian atau menutup sheet induk.

**Keranjang:** bedakan jumlah tersimpan dari suntingan lokal. Perbarui menyimpan tanpa berpindah; Lanjut pesan mengirim jumlah yang terlihat melalui validasi server sebelum membuka checkout produk tersebut, termasuk tanpa JavaScript. Mengubah jumlah tidak berarti memesan stok, menentukan harga akhir, atau membuat pembayaran.

**Logo saat scroll:** satu permukaan kaca untuk satu kontrol. Saat pill merapat, latar dan area tekan bertemu pada lingkaran 52 × 52 px di pusat header. Tombol tidak menggambar oval atau bayangan kedua. Monogram 140:76 dipusatkan tanpa dipotong atau direntangkan; ring fokus hanya muncul untuk navigasi keyboard.

Saat logo ringkas dibuka jauh di bawah halaman, fokus masuk ke kontrol navigasi yang terlihat. Escape mengembalikan fokus ke logo; berpindah fokus ke konten merapatkan navbar tanpa mengambil fokus kembali. Jika pengguna sedang memilih menu atau tanggal, panel terdalam menangani Escape lebih dulu.

**Memperbaiki hasil:** sediakan pemicu Cari & filter di kepala daftar; ini membuka sheet yang sama, bukan filter kedua. Escape mengembalikan fokus ke pemicu asal. Paginasi, hapus kata, dan reset filter mengarah ke daftar, bukan hero editorial. Bedakan katalog kosong dari pencarian tanpa kecocokan.

**Galeri detail:** tampilkan foto utama sekali di depan; pertahankan urutan foto lain dan teks alternatif. Jangan memutar foto otomatis. Sediakan kontrol sentuh, panah keyboard, penghitung, serta pembesaran tanpa meninggalkan produk. Escape mengembalikan fokus; tanpa JS setiap foto tetap dapat dibuka lewat tautan biasa. Jangan tampilkan navigasi foto untuk monogram kosong.

Geser horizontal satu jari pada foto (minimal 44 px) berpindah satu foto saat jari dilepas, baik di kartu detail maupun panel perbesar. Ketuk tetap membuka foto; gerak vertikal, pembatalan, dan cubit untuk zoom bukan perintah pindah. Jangan hilangkan tombol panah ketika gesture tersedia.

**Memilih foto tertentu:** untuk dua foto atau lebih, tampilkan deret pratinjau dengan satu penanda aktif yang juga terbaca tanpa warna. Klik/ketuk langsung memilih foto; Tab cukup sekali memasuki deret. Panah berpindah pilihan, Home/End menuju ujung. Sinkronkan pilihan dengan tombol, geser, dan lightbox. Jika deret melampaui lebar layar, geser deret itu saja, bukan halaman di bawahnya.

**Checkout:** ringkasan sebelum submit menampilkan subtotal, ongkir, pembulatan bila ada, total dan pembagian pembayaran. Untuk DP, pisahkan pokok dari kode unik pada tagihan. Jelaskan tunai saat ambil/antar sesuai cara serah; jangan beri kesan bahwa perkiraan sudah menjadi tagihan. Bila jumlah belum valid, jangan tampilkan total nol palsu. Waktu kelayakan DP mengacu ke WIB, bukan zona perangkat.

**Tagihan aktif:** tampilkan pokok, kode unik, dan nominal transfer persis dari server. Salin hanya digit nominal akhir; menyalin bukan konfirmasi. Jangan tawarkan tindakan transfer pada bagian tunai atau tagihan tidak aktif. Ketika waktu habis, gantikan instruksi dengan peringatan tanpa membacakan setiap detik ke pembaca layar.

**Rincian pelanggan:** tampilkan riwayat perubahan nyata, bukan prediksi tahapan. Status permohonan pembatalan berbeda dari status pesanan. Ganti formulir dengan catatan permohonan yang sudah dikirim agar tidak mengundang pengiriman ulang. Refresh 60 detik hanya ketika halaman aktif dan tidak sedang digunakan (tidak menghapus alasan yang diketik, fokus keyboard, atau panel terbuka).

**Daftar pelacakan:** Semua/Aktif/Arsip adalah tautan filter server khusus pelanggan yang sedang masuk. Arsip mencakup selesai, dibatalkan, dan kedaluwarsa; arsip bukan berarti lunas. Tampilkan total pesanan, bukan nominal instruksi transfer. Simpan kelompok, halaman, dan penanda kartu ketika pelanggan kembali dari rincian. Sediakan jalan keluar dari kelompok kosong dan halaman di luar jangkauan.

**Profil pelanggan:** bedakan nama tersimpan dari draf dan status persetujuan versi aktif. Batalkan perubahan hanya memulihkan draf lokal tanpa mengirim data. Setelah kegagalan validasi, jangan memindahkan centang persetujuan dari versi lama ke versi baru. Tanpa JavaScript, form tetap menggunakan validasi server dan persetujuan eksplisit.

**Tombol kembali:** gunakan `x-back-link` di awal halaman turunan sebelum isi panjang (termasuk galeri). Sebutkan tujuan dan gunakan rute GET internal, bukan `history.back()`, URL bebas dari query, atau submit. Checkout dari keranjang kembali ke baris produk; dari detail kembali ke produk dengan konteks koleksi. Tagihan menuju rincian, rincian menuju kartu riwayat, editor owner menuju daftar kelola. Pada beranda, daftar utama Lacak/Katalog, dasbor, dan agenda utama peran, navigasi global tetap menjadi pintu keluar; jangan membuat tombol kembali yang menuju halaman sendiri. Panel sementara tetap menggunakan Tutup/Escape.

**Agenda staf:** filter jenis tugas dan tanggal WIB adalah navigasi GET, bukan pencatatan serah terima. Ganti tanggal mempertahankan jenis tugas; ganti jenis mempertahankan tanggal dan memulai halaman pertama. Pada hasil kosong sediakan jalan kembali ke semua tugas pada tanggal tersebut. Tampilkan tindakan dan saldo tunai dalam kartu pesanan terkait, bukan sebagai tindakan global.

**Antrean produksi:** bedakan filter tahap (GET) dari perubahan status (POST). Satu kartu hanya menawarkan langkah yang dapat dijalankan saat data dibaca. Untuk papan yang teksnya belum lengkap, jelaskan hambatan alih-alih menampilkan tombol yang pasti ditolak. Periksa kembali kelayakan di server di dalam transaksi agar tab lama tidak menggandakan riwayat.

**Hitung kas antar:** bedakan tagihan setor, kumulatif tercatat, dan hitungan fisik baru. Jangan mengisi otomatis atau mencentang pemeriksaan. Tampilkan kurang/lebih sebagai nominal, bukan warna saja. Selisih dan koreksi membutuhkan catatan. Ringkasan lintas pembayaran tidak boleh menutup kekurangan dengan kelebihan dari transaksi lain.

**Respons pencatatan operasional:** jika validasi ditolak (422), pertahankan isian dan arahkan fokus ke kesalahannya. Jika koneksi terputus, respons tidak dapat dibaca, atau status bentrok, jangan menganggap transaksi gagal: kunci pengiriman ulang dan berikan tautan GET untuk memeriksa keadaan server. Tautan ini tidak boleh mengulangi POST. Konfirmasi yang perlu rekonsiliasi bukan pembayaran terverifikasi.

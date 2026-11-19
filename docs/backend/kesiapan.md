# Gerbang rilis backend

Status: **belum siap menerima transaksi nyata**. Sumber aturan bisnis: `docs/SRS.html`, bukan teks sukses dari view atau komentar controller. `pending.transaction` mengembalikan HTTP 503 sebelum operasi yang belum selesai; jangan menghapusnya secara global.

| Area | Implementasi saat ini | Bukti yang harus ada sebelum rilis |
|---|---|---|
| Identitas staf | Dua guard, login staf aktif, throttle, rotasi sesi, logout, ubah sandi sendiri, pembatasan peran di server. Seeder demo dibatasi ke lokal/test. | Uji semua role × aksi, penonaktifan di tengah sesi, rotasi sesi lain setelah ubah sandi, prosedur provision owner pertama tanpa sandi bawaan. |
| Identitas pelanggan | Template autentikasi WhatsApp Cloud API, hash OTP, TTL, batas percobaan, cooldown per nomor, pembatasan per IP, challenge sesi sekali pakai; profil dan riwayat berdasarkan pengguna login. | Verifikasi template dan pengiriman ke nomor uji di staging; uji resend bersamaan, provider timeout, privasi log, retensi OTP, persetujuan kebijakan, anonimisasi dan ekspor data. |
| Katalog | Relasi kategori sesuai FK produk. Cek tanggal sewa melihat unit fisik aktif, varian aktif, blokir kalender dan reservasi satu tanggal. Ini **bukan reservasi**. | Rekonsiliasi data instalasi lama antara `kategoris` dan `kategori` berdasarkan slug/FK (jangan menghapus tabel lama). Uji kelola media/opsi/varian/unit, rentang tanggal, stok jual, kapasitas custom dan lead time. |
| Checkout situs & input manual | Checkout sewa standar tersedia khusus staging secara opt-in; produksi dan input manual tetap ditutup. | Satu transaksi untuk nomor BK-YYMM-NNNN, snapshot harga, opsi, ongkir, kapasitas, unit × hari, tagihan, audit. Dua koneksi berebut unit terakhir → tepat satu sukses; request ulang tidak menggandakan pesanan. |
| Keuangan | Halaman dan aksi pembayaran yang masih memakai placeholder ditutup. | Tarif DP/lunas dari konfigurasi, rounding, nominal tertunda unik, bukti privat, konfirmasi bukan verifikasi, cek mutasi oleh pihak berizin, tunai/refund dan perubahan saldo atomik. Uji retry dan konflik verifikasi vs expiry. |
| Produksi | T05/T06/T07 mengunci pesanan, memeriksa jenis item/status dan mencatat pelaku serta riwayat. | Uji pesanan campuran, teks custom terkunci, klik ulang dan dua staf bersamaan. |
| Serah, retur, pembatalan | Agenda baca tersedia; aksi belum siap ditutup. | T08–T14: saldo nol, jaminan, kondisi & foto privat per unit, denda/kerusakan, refund sesuai waktu/status, pelepasan slot dan rekonsiliasi. |
| Scheduler, laporan dan operasi | Belum tervalidasi. | Penjadwal expiry idempoten, timezone bisnis, kapasitas dan blokir, laporan angka vs ledger, CSV anonim, audit perubahan, kebijakan file/backup, latihan restore dan pemantauan kegagalan. |

## Verifikasi

Gunakan **database MariaDB khusus pengujian**; migrasi CHECK saat ini memakai DDL MariaDB, bukan SQLite. Workflow `.github/workflows/backend.yml` menyiapkan MariaDB, PHP, dependensi dan build sebelum PHPUnit. Jangan arahkan RefreshDatabase ke basis data produksi. Untuk OTP, isi `WHATSAPP_*` melalui secret environment; template autentikasi harus disetujui dengan tombol salin kode. Tanpa konfigurasi, hasil yang benar adalah layanan tidak tersedia, bukan sukses palsu. Jangan kirim token atau kode OTP ke log/chat.

Untuk tiap gate: kaitkan aturan SRS dengan handler, otorisasi, validasi, efek DB, pengujian sukses/ditolak/ulang dan rollback. Buka aksi individual setelah uji integrasi di MariaDB dan uji alur browser lulus.

## Tahap backend: pelepasan penahanan (T03/T04)

`ReleaseOrderHold` mengunci baris pesanan sebelum memeriksa status. Pelepasan reservasi, pelepasan nominal pembayaran menunggu, pengembalian stok siap jual, perubahan status dan riwayat terjadi dalam satu transaksi. Pesanan dengan penerimaan dana tercatat tidak boleh dilepas otomatis. Ini bukan proses refund. Semua jalur verifikasi pembayaran mendatang wajib mengambil lock pesanan yang sama terlebih dahulu.

Perintah `php artisan pesanan:expire` terdaftar setiap menit. Aktifkan `php artisan schedule:run` dari cron **di staging dengan database uji dahulu**; menjalankan web server saja tidak menjalankan scheduler. Pastikan jam dan basis data sesuai Asia/Jakarta. T04 sudah tersedia di lapisan backend tetapi route publiknya tetap di balik `pending.transaction` sampai pengujian MariaDB dan rekonsiliasi stok lama selesai. Fixture `ReleaseOrderHoldTest` mencakup expiry berulang, isolasi pelanggan serta pesanan dengan dana yang sudah masuk. Sebelum produksi, uji dua koneksi database independen untuk konflik pembatalan, expiry dan verifikasi pembayaran.

Untuk stok barang siap jual, checkout wajib menulis bukti `stok_ditahan` bersama pengurangan stok; tanpa bukti itu pesanan lama harus direkonsiliasi, bukan otomatis menambah stok berdasarkan jumlah di item. Jangan membuat bukti untuk data lama tanpa mencocokkannya dengan stok aktual.

## Checkout staging: sewa standar (T01)

`POST /pelanggan/pesanan` tetap 503 secara default dan selalu 503 di production. Hanya untuk database staging/lokal khusus: jalankan migrasi, atur `RENTAL_CHECKOUT_STAGING=true` dan `CHECKOUT_PRIVACY_VERSION` sesuai kebijakan yang ditampilkan, perbarui config cache, dan jalankan scheduler. Jangan memasukkan pembayaran nyata. Untuk mencoba gunakan sesi pelanggan aktif dengan nomor terverifikasi dan nama terisi; POST web memerlukan CSRF.

Kontrak: `kunci` (UUID baru per niat checkout; pertahankan saat retry), `produk_id`, `varian_id`, `jumlah`, `tanggal` (YYYY-MM-DD), `alamat_antar`, `zona_antar_id`, `pembayaran` (`dp`/`penuh`), `setuju_privasi` dan `versi_privasi`. Jalur ini hanya menangani satu varian sewa standar tanpa opsi, satu tanggal, dengan pengantaran. Zona yang dipilih harus aktif dan batasnya ≤15 km; penentuan zona berdasarkan koordinat alamat belum terintegrasi. Bukan jalur checkout jual/custom, pesanan manual atau keranjang campuran. Tarif diambil dari varian, bukan dari browser. Tidak ada pembebanan dana atau verifikasi transfer saat checkout.

Nomor bulanan, item per unit, reservasi fisik, tagihan, persetujuan privasi dan riwayat ditulis dalam satu transaksi. Retry dengan UUID dan payload yang sama mengembalikan pesanan lama (termasuk jika sudah kedaluwarsa); UUID sama dengan payload berbeda ditolak. Hari puncak bersumber dari tabel `tanggal_puncak` (daftar kosong berarti tidak ada hari puncak). Migrasi instalasi lama harus memastikan penghitung per bulan sesuai nomor yang telah diterbitkan. Uji `php artisan test --filter=RentalCheckoutTest` pada **MariaDB khusus tes**: tes membuat ulang skema dan menjalankan dua proses PHP dengan koneksi DB terpisah. Sebelum membuka produksi masih harus ada verifikasi pembayaran, resolusi zona/lead time produk, konfigurasi biaya, bukti uji konkurensi, rekonsiliasi, dan prosedur penanganan pembayaran terlambat.

## Verifikasi transfer awal (T02, staging)

Hanya owner/admin yang telah mengecek mutasi bank boleh POST ke `admin.pesanan.verify-payment` untuk checkout sewa staging, dengan `pembayaran_id`, `nominal` rupiah yang benar-benar terlihat pada mutasi, dan `referensi` mutasi unik (sertakan identitas bank/rekening dalam namespace referensi; jangan memakai nomor pesanan sebagai pengganti ID transaksi bank). Bukan endpoint unggah bukti pelanggan. Production selalu ditutup. Tidak mengirim uang atau menghubungi bank.

- Cocok sebelum batas, tagihan masih menunggu, dan tiap item masih memegang reservasinya: HTTP 200, T02 dan saldo diperbarui atomik. Kode unik tercatat sebagai selisih antara nominal diterima dan pokok yang dikreditkan.
- Pada/setelah batas penahanan, pesanan dilepas melalui T03 meski scheduler terlambat. Uang masuk terlambat, nominal berbeda, atau transfer kedua dicatat di `mutasi_masuk` sebagai `perlu_rekonsiliasi` (HTTP 202); bukan pembayaran terverifikasi, bukan refund otomatis. Waktu pembanding adalah waktu verifikasi server setelah lock, bukan waktu yang dikirim browser.
- Retry referensi dan nominal yang sama pada tagihan yang sama mengembalikan hasil lama; penggunaan ulang dengan data berbeda menghasilkan 409. Pemeriksaan dan expiry mengunci baris pesanan yang sama. Audit staf dan jumlah bruto tersimpan bersama hasilnya.
- Implementasi ini belum menerbitkan tagihan pelunasan, memproses T13/T14, atau menyelesaikan rekonsiliasi. Jangan buka produksi sebelum seluruh jalur tersebut dan pengujian MariaDB lulus.

## Pembaruan: pelunasan sewa staging

Verifikasi DP sekarang membuat **satu** tagihan `pelunasan` senilai saldo tersisa, dalam transaksi yang sama dengan kredit DP. Nominal transfer dibedakan dari transfer DP sebelumnya. Endpoint verifikasi yang sama menerima ID tagihan pelunasan: bila cocok dan tepat waktu, hanya saldo dan pembayaran yang diperbarui; status produksi tidak mundur. Setelah `batas_pelunasan_pada`, penerimaan masuk daftar rekonsiliasi tanpa membatalkan pesanan yang sudah menerima DP. Ini menggantikan catatan sebelumnya tentang belum adanya tagihan pelunasan. Resolusi dana telat/berbeda dan refund tetap belum diaktifkan.

### Uji mandiri tanpa konfigurasi `.env`

Pada komputer yang memiliki **Docker dengan Compose v2** dan salinan kode branch ini, jalankan dari folder yang memuat `artisan`:

```sh
docker compose -f compose.backend-test.yaml up --build --abort-on-container-exit --exit-code-from test
```

Runner ini memasang dependensi PHP/JS di image, lalu menjalankan seluruh PHPUnit dengan MariaDB 11.4 khusus tes. Tidak ada port database yang dipublikasikan, direktori project tidak di-mount, dan data tes disimpan dalam tmpfs. Tidak perlu kredensial WhatsApp, akun owner asli, `.env` produksi, atau menjalankan `migrate:fresh` pada mesin lokal. Build pertama memerlukan koneksi internet. Jika gagal, simpan nama tes dan pesan error pertama sebelum membersihkan container:

```sh
docker compose -f compose.backend-test.yaml logs test
docker compose -f compose.backend-test.yaml down -v
```

Keputusan operasional: setujui teks dan versi kebijakan privasi, template OTP dan nomor uji di staging; tentukan siapa yang menyelesaikan daftar rekonsiliasi dan menyetujui refund. Jangan pernah membagikan token/OTP/rekening pelanggan untuk debugging.

### Rekonsiliasi: pencatatan transfer keluar penuh (staging)

Admin/owner membaca antrean JSON di `GET /admin/rekonsiliasi`. Untuk dana masuk **yang tidak dikreditkan**, setelah mencocokkan mutasi masuk dan pengembalian yang **sudah dilakukan** pada rekening, catat `POST /admin/rekonsiliasi/{mutasi}/pengembalian` dengan `referensi_keluar` unik, `nominal` persis sebesar mutasi masuk dan `alasan`. Di staging gunakan data simulasi, bukan transfer bank nyata. Respons pencatatan pertama 201; retry identik 200; data/referensi bentrok 409. Catatan dan audit disimpan atomik. Operasi ini tidak mengirim uang, tidak melunasi tagihan, tidak mengubah saldo pesanan, dan tidak memesan ulang unit. Ini bukan refund proporsional pembatalan atas pembayaran terverifikasi (BR-13), bukan penerimaan selisih nominal, dan bukan reaktivasi T13. Fitur-fitur tersebut tetap tertutup.

### Pembatalan pesanan sewa berbayar (T12 / BR-13, staging)

1. Admin/owner memutuskan `POST /admin/pesanan/{id}/cancel-paid` dengan `alasan`. Hanya checkout sewa standar berstatus dikonfirmasi, sedang diproduksi, atau siap. Persentase refund adalah minimum persentase waktu (H-2 atau lebih: 100%, H-1: 50%, hari acara atau lewat: 0%) dan tahap (dikonfirmasi: 100%, produksi: 50%, siap: 0%). Hitung dari **pokok yang telah terverifikasi**, bukan total pesanan, jaminan, atau kode unik. Waktu keputusan memakai Asia/Jakarta; pecahan rupiah dibulatkan ke bawah. Biaya transfer awal nol. Owner dapat mengirim `nominal_override` beserta `alasan_override`, tidak lebih dari pokok dibayar. Nilai sistem dan final tetap disimpan.
2. Keputusan melepaskan unit, menutup tagihan masuk tertunda dan saldo tertagih, tetapi tidak menghapus histori pembayaran masuk. Refund nol tidak membuat tagihan keluar. Keputusan berulang identik tidak menghitung ulang meski hari berganti.
3. Setelah memeriksa transfer keluar (simulasi di staging), catat `POST /admin/pesanan/{id}/record-refund` dengan `referensi_keluar` dan `nominal` persis sebesar keputusan. Akui sebagai arus **keluar** (`jenis=refund`), bukan pendapatan. Satu referensi unik untuk kedua jenis pengembalian: T12 dan rekonsiliasi mutasi belum dikreditkan. Migrasi menyalin referensi historis dari `pengembalian_mutasi`; jalankan sebelum endpoint dipakai. Tidak ada instruksi bank otomatis, refund parsial bertahap, maupun pengaktifan kembali unit secara paksa. Permintaan pembatalan mandiri setelah bayar, T13/T14, dan laporan rekonsiliasi tetap merupakan pekerjaan terpisah.

# Uji lapangan Kurir — lembar penerimaan staging

**Status: belum dijalankan oleh kurir lapangan.** Gunakan akun dan pesanan uji, uang simulasi, serta foto benda uji. Jangan gunakan foto KTP/KTM, nomor pelanggan asli, transfer nyata, atau mengirim pesan WhatsApp ke pihak luar. Siapkan satu sewa dengan dua unit dan satu tugas buket; catat versi aplikasi, model ponsel, browser, tanggal WIB dan penguji. Simpan bukti tanpa data pribadi.

| Percobaan | Hasil yang harus diperiksa | Hasil aktual |
|---|---|---|
| Temukan nomor pada Antar, pindah Jemput, ganti tanggal lalu hapus pencarian | Nomor, tanggal dan jenis tugas tidak saling terhapus tanpa diminta. Tugas ambil di toko tidak membuka peta. | Belum diuji |
| Buka kontak pada perangkat dengan/tanpa WhatsApp | Periksa nomor dan teks yang memuat nomor pesanan, tanpa alamat atau informasi jaminan. Batalkan sebelum mengirim. | Belum diuji |
| Kamera belakang, izin kamera ditolak, kembali dari kamera, pilih dari galeri | Formulir tetap dapat memakai pemilih berkas. Foto melekat pada unit benar, dapat dihapus tanpa mengirim. | Belum diuji |
| Dua unit, cacat pada satu; coba foto ketujuh atau berkas terlalu besar | Batas 1–6 berlaku untuk seluruh tahap, bukan per unit. Unit cacat wajib memiliki catatan dan foto sendiri. | Belum diuji |
| Jaminan KTP/KTM lalu deposit; bedakan saldo tunai dengan deposit | Tidak ada unggah identitas. Hanya deposit meminta nominal, pembayaran tunai punya konfirmasi terpisah. | Belum diuji |
| Putus jaringan sebelum/saat unggah, pulihkan koneksi | Untuk hasil tidak pasti jangan kirim ulang. Muat ulang agenda melalui GET, periksa status beserta bukti di Admin sebelum mengulang dari tugas yang masih terbuka. | Belum diuji |
| Jemput unit terlambat atau diperpanjang | Tanggal dan jam batas terbaru terlihat. Periksa kondisi kembali, bukti privat, perpindahan tugas dan saldo di Admin. | Belum diuji |

Jika beda hasil, catat tahap, waktu, jumlah unit/foto dan apakah transaksi tercatat **sekali** di server. Jangan meluluskan skenario hanya karena tombol menghilang. Penguji dan penanggung jawab operasional harus menyepakati hasil sebelum penggunaan nyata.

**Bukti otomatis terpisah:** setelah tiap tahap Chrome dua unit, CI membaca kembali penyimpanan privat dan database uji. Pada jam 18.01 WIB dengan tarif Rp10.000/jam/unit dan tanpa perpanjangan, diharapkan satu denda Rp20.000; deposit Rp100.000 bukan pembayaran denda. Ini bukan hasil uji lapangan.

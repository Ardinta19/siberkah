erDiagram
    pelanggan ||--o{ pesanan : memesan
    pengguna |o--o{ pesanan : "input manual"
    zona_antar |o--o{ pesanan : "ongkir antar"
    zona_antar ||--o{ kecamatan_antar : "memuat (rencana)"
    pesanan ||--|{ item_pesanan : berisi
    item_pesanan ||--o{ opsi_item_pesanan : "punya opsi"
    pesanan ||--o{ penyesuaian_pesanan : disesuaikan
    pesanan ||--|{ riwayat_status_pesanan : "tercatat di"
    pengguna |o--o{ penyesuaian_pesanan : mencatat
    pengguna |o--o{ riwayat_status_pesanan : mengubah

    pengguna {
        bigint id PK
        string nama
        string surel UK
        string kata_sandi
        string peran
        boolean aktif
        dateTime masuk_terakhir_pada
        string remember_token
        timestamp dibuat_pada
        timestamp diubah_pada
    }
    pelanggan {
        bigint id PK
        string nomor_wa UK "format 628xx"
        string nama
        boolean aktif
        dateTime nomor_terverifikasi_pada
        dateTime masuk_terakhir_pada
        dateTime kebijakan_disetujui_pada "rencana migrasi 10"
        string versi_kebijakan "rencana migrasi 10"
        dateTime dihapus_pada
        timestamp dibuat_pada
        timestamp diubah_pada
    }
    kode_otp {
        bigint id PK
        string nomor_wa "tanpa FK, terikat lewat nomor"
        string kode_hash
        string alamat_ip
        dateTime kedaluwarsa_pada
        int percobaan
        dateTime dipakai_pada
        string status_kirim
        timestamp dibuat_pada
    }
    zona_antar {
        bigint id PK
        string nama
        decimal jarak_maks_km UK
        int ongkir
        boolean aktif
    }
    kecamatan_antar {
        bigint id PK
        bigint zona_antar_id FK "rencana migrasi 11"
        string nama_kecamatan UK "rencana migrasi 11"
    }
    urutan_nomor_pesanan {
        string periode PK "YYMM"
        int terakhir
    }
    pesanan {
        bigint id PK
        string nomor UK "BK-YYMM-NNNN"
        bigint pelanggan_id FK
        string status "9 nilai, lihat cek_status_pesanan"
        string asal "situs atau admin"
        bigint pengguna_id FK "terisi jika asal admin"
        string nama_pemesan
        string nomor_wa_pemesan
        string cara_serah "ambil atau antar"
        text alamat_antar
        bigint zona_antar_id FK
        date tanggal_serah
        time jam_serah_mulai
        time jam_serah_selesai
        text catatan_pelanggan
        int subtotal
        int ongkir
        int penyesuaian "bertanda"
        int total
        int jumlah_dibayar
        int saldo "bertanda"
        boolean wajib_dp
        dateTime tahan_hingga_pada
        dateTime batas_pelunasan_pada
        string jenis_jaminan
        int nominal_jaminan
        dateTime jaminan_kembali_pada
        int persen_refund "0 sampai 100"
        int nominal_refund
        text alasan_batal
        dateTime dikonfirmasi_pada
        dateTime diserahkan_pada
        dateTime dikembalikan_pada
        dateTime selesai_pada
        dateTime dibatalkan_pada
        timestamp dibuat_pada
        timestamp diubah_pada
    }
    item_pesanan {
        bigint id PK
        bigint pesanan_id FK
        bigint unit_sewa_id FK "terisi untuk sewa"
        string nama_produk "salinan saat pesan"
        string nama_varian "salinan saat pesan"
        int jumlah "1 per baris untuk sewa"
        int harga_satuan
        int subtotal
        date tanggal_mulai
        date tanggal_selesai
        string tulisan_kustom
    }
    opsi_item_pesanan {
        bigint id PK
        bigint item_pesanan_id FK "CASCADE"
        bigint opsi_produk_id FK "SET NULL"
        bigint nilai_opsi_id FK "SET NULL"
        string nama_opsi
        string label_nilai
        int harga_tambahan
        string teks_bebas
    }
    penyesuaian_pesanan {
        bigint id PK
        bigint pesanan_id FK
        string jenis "denda_telat, kerusakan, perpanjangan, diskon_manual, biaya_admin_refund"
        string keterangan
        int nominal "bertanda"
        bigint pengguna_id FK
    }
    riwayat_status_pesanan {
        bigint id PK
        bigint pesanan_id FK
        string status_lama
        string status_baru
        string kode_transisi "T01 sampai T14"
        string pelaku "pelanggan, staf, sistem"
        bigint pengguna_id FK
        string catatan
        timestamp dibuat_pada
    }

    erDiagram
    kategori ||--o{ produk : mengelompokkan
    produk ||--o{ varian_produk : "punya varian"
    produk ||--o{ opsi_produk : "punya opsi"
    opsi_produk ||--o{ nilai_opsi : "punya nilai"
    produk ||--o{ gambar_produk : "punya gambar"
    varian_produk |o--o{ gambar_produk : "gambar khusus varian"

    kategori {
        bigint id PK
        string nama "kolom lain menyusul dari db:table"
    }
    produk {
        bigint id PK
        bigint kategori_id FK
        string nama
        string slug UK
        text deskripsi
        string jenis "sewa atau pesan_khusus"
        int harga_dasar
        string kelompok_kapasitas "boleh kosong"
        boolean aktif
        int urutan
    }
    varian_produk {
        bigint id PK
        bigint produk_id FK
        string nama "UK bersama produk_id"
        string awalan_kode "awalan kode unit, misal AKR"
        int harga_tambahan
    }
    opsi_produk {
        bigint id PK
        bigint produk_id FK
        string nama
        string tipe "pilih_satu, boleh_banyak, teks_bebas"
    }
    nilai_opsi {
        bigint id PK
        bigint opsi_produk_id FK "CASCADE"
        string label
        int harga_tambahan
        int stok "kosong berarti tak terbatas"
    }
    gambar_produk {
        bigint id PK
        bigint produk_id FK
        bigint varian_produk_id FK "boleh kosong"
        string jalur_berkas
        string teks_alternatif
        int urutan
    }

    erDiagram
    varian_produk ||--o{ unit_sewa : "punya unit"
    unit_sewa ||--o{ hari_reservasi : "ditahan pada"
    pesanan ||--o{ hari_reservasi : menahan
    item_pesanan ||--o{ hari_reservasi : menahan
    unit_sewa |o--o{ item_pesanan : "dipakai oleh"
    produk |o--o{ blokir_kalender : "diblokir untuk"
    pengguna |o--o{ blokir_kalender : mencatat

    unit_sewa {
        bigint id PK
        bigint varian_produk_id FK
        string kode UK "misal AKR-001"
        string status "aktif, perawatan, rusak, pensiun"
        string catatan
    }
    hari_reservasi {
        bigint id PK
        bigint unit_sewa_id FK "UK bersama tanggal: uniq_unit_tanggal"
        date tanggal "UK bersama unit_sewa_id"
        bigint pesanan_id FK
        bigint item_pesanan_id FK
    }
    blokir_kalender {
        bigint id PK
        date tanggal
        bigint produk_id FK "kosong berarti seluruh toko"
        string alasan
        bigint pengguna_id FK
    }
    kapasitas_produksi_harian {
        bigint id PK
        date tanggal "UK bersama kelompok_kapasitas"
        string kelompok_kapasitas "terikat ke produk lewat teks, tanpa FK"
        int batas
    }

    erDiagram
    pesanan ||--o{ pembayaran : "dibayar lewat"
    rekening_pembayaran |o--o{ pembayaran : "masuk ke"
    pengguna |o--o{ pembayaran : memverifikasi
    item_pesanan ||--o{ inspeksi_sewa : "diperiksa pada"
    inspeksi_sewa ||--o{ foto_inspeksi : "punya foto"
    pengguna ||--o{ inspeksi_sewa : memeriksa
    pengguna |o--o{ log_aktivitas : melakukan
    pelanggan |o--o{ log_aktivitas : melakukan

    pembayaran {
        bigint id PK
        bigint pesanan_id FK
        string jenis "dp, pelunasan, penuh, refund"
        string status "menunggu, terverifikasi, ditolak, kedaluwarsa"
        int tagihan
        int kode_unik "1 sampai 999, boleh kosong"
        int nominal_diharapkan "tagihan + kode_unik"
        int nominal_tertunda UK "uniq_nominal_tertunda"
        int nominal_diterima
        bigint rekening_pembayaran_id FK
        dateTime dikonfirmasi_pelanggan_pada
        dateTime diterima_pada
        bigint pengguna_id FK
        dateTime diverifikasi_pada
        string catatan_verifikasi
        timestamp dibuat_pada
        timestamp diubah_pada
    }
    rekening_pembayaran {
        bigint id PK
        string nama_bank
        string nomor_rekening
        string atas_nama
    }
    inspeksi_sewa {
        bigint id PK
        bigint item_pesanan_id FK "UK bersama tahap"
        string tahap "serah atau kembali"
        string kondisi "baik, cacat_ringan, rusak"
        bigint pengguna_id FK
        dateTime dilakukan_pada
    }
    foto_inspeksi {
        bigint id PK
        bigint inspeksi_sewa_id FK "CASCADE"
        int urutan "UK bersama inspeksi, 1 sampai 6"
    }
    tarif_kerusakan {
        bigint id PK
        string kode UK
        string nama
        int tarif
        string satuan
    }
    pengaturan {
        bigint id PK
        string kunci UK
        string nilai
        string tipe
        string keterangan
    }
    log_aktivitas {
        bigint id PK
        bigint pengguna_id FK "boleh kosong"
        bigint pelanggan_id FK "boleh kosong"
        string aksi
        string subjek_tabel
        bigint subjek_id
        json sebelum
        json sesudah
        string alamat_ip
        timestamp dibuat_pada
    }

    
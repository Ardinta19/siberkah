# Audit backend SIBERKAH — batas bukti

Rujukan persyaratan: `docs/SRS.html`. Kontrak, pengujian dan pembatasan per paket ada di `kesiapan.md`. Ini daftar kerja, bukan pernyataan lulus produksi. Rute staging, respons API, UI, hasil CI, dan penerimaan operator bukan hal yang sama.

| Kelompok SRS | Implementasi/bukti di repo | Yang masih harus diperiksa/diselesaikan |
|---|---|---|
| AUTH-01/02/03/05/06/07/10/11, NFR-09/13/15 | OTP sesi + kuota nomor/IP, guard staf/pelanggan, ganti nama, anonimisasi terjaga, bantuan manual; IdentityFlow/OtpQuota/RouteAccessMatrix/AnonymizeCustomer | Kirim OTP sungguhan, cache bersama dan kebijakan sesi di hosting; tinjauan bukti privat/catatan bebas |
| AUTH-08/09, AKN-02 | Wajib rotasi sandi, CLI buat/nonaktif, penjagaan Owner terakhir (StaffProvisioningTest); consent checkout disimpan | Nama+consent versi/waktu saat onboarding (bukan hanya checkout) |
| KAT-01–07, KTL-01–05, LGL/KON/ANI | Filter, ketersediaan, urut, saran tanggal, varian/opsi, galeri WebP, halaman legal/kontak | Audit teks/aksesibilitas, kontak lokasi nyata, alur pilih-dan-pesan serta pratinjau UI; audit publish/draf |
| PSN-01–08/10/11/13–15, STK-01 | Checkout, stok/kuota, harga snapshot, nomor, scheduler, pembayaran, reaktivasi, tiga kolom papan/lock T05; tes rental/buket dan konkurensi | Pratinjau, validasi UX jumlah langkah, cron nyata. Keranjang campur rental+buket tidak didukung |
| BYR-01/02/04–11 | Mutasi aktual, keputusan selisih, invoice/tenggat, DP, tunai, deposit dan refund dengan bukti | BYR-10/11: rekening tujuan dan QRIS statis yang dapat dikelola Owner, petunjuk dan hitung mundur. BYR-05 unggah bukti opsional. Kurang pokok tidak dianggap lunas |
| AKN-01, NTF-02 | Pesanan milik sendiri, riwayat, refresh 60 detik dan draft wa.me | Uji browser dan nomor kontak usaha; bukan pengiriman status otomatis |
| OPS-01–10, ADJ-01 | Endpoint spesifik perubahan status, pesanan manual, blokir, agenda per peran, inspeksi, damage/deposit, perpanjangan, dashboard | Audit agenda per jam/peta dan kelengkapan detail antrean produksi. Endpoint generik lawas sengaja terkunci; jangan aktifkan stub |
| AUD-01, SET-01, LAP-01/03 | Log baca Owner, pengaturan efektif terbatas, penerimaan terverifikasi dan CSV anonim, arus bank terpisah | Audit sebelum/sesudah untuk semua perubahan penting dan konfigurasi bisnis/tarif yang belum tersedia |
| NFR-04/05/27/28 dan operasi | Benchmark HTTP sintetis, uji konkurensi, latihan restore DB + disk publik/privat | Hasil CI bukan SLA hosting; backup offsite terjadwal, pemulihan kunci, RPO/RTO nyata dan pengujian beban gabungan operator |

Prioritas lanjutan: (1) onboarding pelanggan, (2) rekening/QRIS beserta petunjuk pembayaran, (3) audit operasi/pengaturan dan verifikasi hosting, baru penerimaan UI halaman demi halaman. Tidak ada dummy rekening, kredensial, bukti transfer atau akun produksi yang boleh di-seed untuk sekadar meluluskan audit.

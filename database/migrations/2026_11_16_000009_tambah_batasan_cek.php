<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT cek_kode_unik
            CHECK (kode_unik IS NULL OR kode_unik BETWEEN 1 AND 999)");

        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT cek_nominal_diharapkan
            CHECK (kode_unik IS NULL OR nominal_diharapkan = tagihan + kode_unik)");

        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT cek_nominal_tertunda
            CHECK (nominal_tertunda IS NULL OR (status = 'menunggu' AND nominal_tertunda = nominal_diharapkan))");

        DB::statement("ALTER TABLE pesanan ADD CONSTRAINT cek_persen_refund
            CHECK (persen_refund IS NULL OR persen_refund BETWEEN 0 AND 100)");

        DB::statement("ALTER TABLE pesanan ADD CONSTRAINT cek_status_pesanan
            CHECK (status IN ('menunggu_pembayaran','kedaluwarsa','dikonfirmasi','sedang_diproduksi','siap','sudah_diserahkan','sudah_dikembalikan','selesai','dibatalkan'))");

        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT cek_status_pembayaran
            CHECK (status IN ('menunggu','terverifikasi','ditolak','kedaluwarsa'))");

        DB::statement("ALTER TABLE foto_inspeksi ADD CONSTRAINT cek_urutan_foto
            CHECK (urutan BETWEEN 1 AND 6)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE foto_inspeksi DROP CONSTRAINT cek_urutan_foto");
        DB::statement("ALTER TABLE pembayaran DROP CONSTRAINT cek_status_pembayaran");
        DB::statement("ALTER TABLE pesanan DROP CONSTRAINT cek_status_pesanan");
        DB::statement("ALTER TABLE pesanan DROP CONSTRAINT cek_persen_refund");
        DB::statement("ALTER TABLE pembayaran DROP CONSTRAINT cek_nominal_tertunda");
        DB::statement("ALTER TABLE pembayaran DROP CONSTRAINT cek_nominal_diharapkan");
        DB::statement("ALTER TABLE pembayaran DROP CONSTRAINT cek_kode_unik");
    }
};
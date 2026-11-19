<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gambar_produk', function (Blueprint $tabel) {
            $tabel->id();

            // Gambar selalu milik satu produk
            $tabel->foreignId('produk_id')->constrained('produk');

            // Kosong berarti gambar umum produk; terisi berarti gambar khusus satu varian (misalnya warna)
            $tabel->foreignId('varian_produk_id')->nullable()->constrained('varian_produk');

            $tabel->string('jalur_berkas');

            // Wajib diisi lewat validasi aplikasi (aksesibilitas); di basis data dibiarkan boleh kosong
            $tabel->string('teks_alternatif')->nullable();

            // Urutan tampil di galeri K-02, mulai dari 1
            $tabel->unsignedSmallInteger('urutan')->default(1);

            // Tabel ini hanya ditambah, tidak diubah, jadi tidak ada diubah_pada
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->index(['produk_id', 'urutan'], 'idx_gambar_produk_urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gambar_produk');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu batas atas per zona (BR-17): 5, 10, 15 km.
        Schema::create('zona_antar', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama', 60);
            $tabel->decimal('jarak_maks_km', 4, 1)->unique();
            $tabel->unsignedInteger('ongkir');
            $tabel->boolean('aktif')->default(true);
            $tabel->catatWaktu();
        });

        Schema::create('tarif_kerusakan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode', 30)->unique();
            $tabel->string('nama', 80);
            $tabel->unsignedInteger('tarif');
            $tabel->string('satuan', 20);
            $tabel->catatWaktu();
        });

        // Semua angka aturan bisnis disimpan di sini, bukan di kode.
        Schema::create('pengaturan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kunci', 60)->unique();
            $tabel->string('nilai', 500);
            $tabel->string('tipe', 15)->default('teks');
            $tabel->string('keterangan', 200)->nullable();
            $tabel->catatWaktu();
        });

        Schema::create('rekening_pembayaran', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama_bank', 40);
            $tabel->string('nomor_rekening', 40);
            $tabel->string('atas_nama', 100);
            $tabel->boolean('aktif')->default(true);
            $tabel->unsignedSmallInteger('urutan')->default(0);
            $tabel->catatWaktu();
        });

        // produk_id kosong berarti seluruh toko tutup pada tanggal itu.
        Schema::create('blokir_kalender', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->date('tanggal');
            $tabel->foreignId('produk_id')->nullable()->constrained('produk')->restrictOnDelete();
            $tabel->string('alasan', 160);
            $tabel->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $tabel->catatWaktu();

            $tabel->index(['tanggal', 'produk_id'], 'idx_blokir_tanggal_produk');
        });

        // Mengganti batas bawaan per tanggal (misalnya 15 buket custom per hari).
        Schema::create('kapasitas_produksi_harian', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->date('tanggal');
            $tabel->string('kelompok_kapasitas', 30);
            $tabel->unsignedSmallInteger('batas');
            $tabel->catatWaktu();

            $tabel->unique(['tanggal', 'kelompok_kapasitas'], 'uniq_kapasitas_tanggal');
        });
    }

    public function down(): void
    {
        foreach (['kapasitas_produksi_harian', 'blokir_kalender', 'rekening_pembayaran', 'pengaturan', 'tarif_kerusakan', 'zona_antar'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};
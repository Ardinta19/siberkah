<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama', 80);
            $tabel->string('slug', 100)->unique();
            $tabel->unsignedSmallInteger('urutan')->default(0);
            $tabel->boolean('aktif')->default(true);
            $tabel->catatWaktu();
        });

        // jenis: jual, sewa, custom.
        Schema::create('produk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $tabel->string('nama', 150);
            $tabel->string('slug', 170)->unique();
            $tabel->string('jenis', 15);
            $tabel->string('kelompok_kapasitas', 30)->nullable();
            $tabel->text('deskripsi')->nullable();
            $tabel->unsignedInteger('harga_dasar')->default(0);
            $tabel->string('foto_utama')->nullable();
            $tabel->boolean('aktif')->default(true);
            $tabel->catatWaktu();

            $tabel->index(['kategori_id', 'aktif'], 'idx_produk_kategori_aktif');
        });

        Schema::create('varian_produk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $tabel->string('nama', 80);
            $tabel->unsignedInteger('harga');
            $tabel->unsignedInteger('stok')->default(0);
            $tabel->boolean('aktif')->default(true);
            $tabel->catatWaktu();
        });

        Schema::create('opsi_produk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $tabel->string('nama', 80);
            $tabel->string('tipe', 20)->default('pilihan');
            $tabel->boolean('wajib')->default(false);
            $tabel->unsignedSmallInteger('urutan')->default(0);
            $tabel->catatWaktu();
        });

        Schema::create('nilai_opsi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('opsi_produk_id')->constrained('opsi_produk')->cascadeOnDelete();
            $tabel->string('label', 80);
            $tabel->unsignedInteger('harga_tambahan')->default(0);
            $tabel->unsignedSmallInteger('urutan')->default(0);
            $tabel->boolean('aktif')->default(true);
            $tabel->catatWaktu();
        });
    }

    public function down(): void
    {
        foreach (['nilai_opsi', 'opsi_produk', 'varian_produk', 'produk', 'kategori'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};
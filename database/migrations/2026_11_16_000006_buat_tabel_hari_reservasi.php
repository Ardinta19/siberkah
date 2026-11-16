<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu unit terpakai pada satu tanggal.
        // Batasan unik di bawah membuat pesanan ganda mustahil, walau kode aplikasi salah.
        Schema::create('hari_reservasi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('unit_sewa_id')->constrained('unit_sewa')->restrictOnDelete();
            $tabel->date('tanggal');
            $tabel->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
            $tabel->foreignId('item_pesanan_id')->constrained('item_pesanan')->restrictOnDelete();
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->unique(['unit_sewa_id', 'tanggal'], 'uniq_unit_tanggal');
            $tabel->index('tanggal', 'idx_reservasi_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_reservasi');
    }
};
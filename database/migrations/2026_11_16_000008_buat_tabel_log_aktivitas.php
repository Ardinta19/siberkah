<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya ditambah, tidak diubah atau dihapus oleh aplikasi.
        Schema::create('log_aktivitas', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pengguna_id')->nullable()->constrained('pengguna')->restrictOnDelete();
            $tabel->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->restrictOnDelete();
            $tabel->string('aksi', 60);
            $tabel->string('subjek_tabel', 40)->nullable();
            $tabel->unsignedBigInteger('subjek_id')->nullable();
            $tabel->json('sebelum')->nullable();
            $tabel->json('sesudah')->nullable();
            $tabel->string('alamat_ip', 45)->nullable();
            $tabel->timestamp('dibuat_pada')->nullable();

            $tabel->index(['subjek_tabel', 'subjek_id'], 'idx_log_subjek');
            $tabel->index('dibuat_pada', 'idx_log_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
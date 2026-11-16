<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris satu benda fisik. status: aktif, perawatan, rusak, pensiun.
        Schema::create('unit_sewa', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('varian_produk_id')->constrained('varian_produk')->restrictOnDelete();
            $tabel->string('kode', 20)->unique();
            $tabel->string('status', 20)->default('aktif');
            $tabel->string('catatan', 200)->nullable();
            $tabel->catatWaktu();

            $tabel->index(['varian_produk_id', 'status'], 'idx_unit_varian_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_sewa');
    }
};
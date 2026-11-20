<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sewa', function (Blueprint $table) {
            $table->foreignId('pesanan_id')->primary()->constrained('pesanan')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->uuid('kunci');
            $table->char('sidik', 64);
            $table->string('versi_privasi', 40);
            $table->dateTime('disetujui_pada');
            $table->unique(['pelanggan_id', 'kunci']);
        });
        Schema::create('tanggal_puncak', function (Blueprint $table) {
            $table->date('tanggal')->primary();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanggal_puncak');
        Schema::dropIfExists('checkout_sewa');
    }
};

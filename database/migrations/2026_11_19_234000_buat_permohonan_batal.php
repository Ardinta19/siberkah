<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan_batal', function (Blueprint $table) {
            $table->foreignId('pesanan_id')->primary()->constrained('pesanan')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->string('alasan', 300);
            $table->string('status', 16)->default('menunggu');
            $table->dateTime('diajukan_pada');
            $table->foreignId('peninjau_id')->nullable()->constrained('pengguna')->restrictOnDelete();
            $table->string('catatan_keputusan', 300)->nullable();
            $table->dateTime('diputuskan_pada')->nullable();
            $table->index(['status', 'diajukan_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_batal');
    }
};

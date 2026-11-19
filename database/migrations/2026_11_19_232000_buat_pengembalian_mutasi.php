<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalian_mutasi', function (Blueprint $table) {
            $table->foreignId('mutasi_masuk_id')->primary()->constrained('mutasi_masuk')->restrictOnDelete();
            $table->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $table->string('referensi_keluar', 100)->unique();
            $table->unsignedInteger('nominal');
            $table->string('alasan', 300);
            $table->dateTime('dicatat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_mutasi');
    }
};

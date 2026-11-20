<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->restrictOnDelete();
            $table->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $table->string('referensi', 100)->unique();
            $table->unsignedInteger('nominal');
            $table->string('hasil', 20);
            $table->string('alasan', 40)->nullable();
            $table->dateTime('dicatat_pada');
            $table->index(['hasil', 'dicatat_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_masuk');
    }
};

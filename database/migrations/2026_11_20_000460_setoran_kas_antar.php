<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('setoran_kas_antar', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pembayaran_id')->constrained('pembayaran')->restrictOnDelete();
            $t->foreignId('kasir_id')->constrained('pengguna')->restrictOnDelete();
            $t->uuid('kunci');
            $t->unique(['pembayaran_id','kunci']);
            $t->unsignedBigInteger('sebelum');
            $t->unsignedBigInteger('sesudah');
            $t->string('catatan', 200)->nullable();
            $t->dateTime('dicatat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_kas_antar');
    }
};

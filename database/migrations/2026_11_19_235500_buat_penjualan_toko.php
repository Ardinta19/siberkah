<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjualan_toko', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan')->restrictOnDelete();
            $t->uuid('kunci')->unique();
            $t->char('sidik', 64);
            $t->string('versi_privasi', 40);
            $t->dateTime('disetujui_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_toko');
    }
};

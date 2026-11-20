<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pemakaian_deposit', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan');
            $t->foreignId('pengguna_id')->constrained('pengguna');
            $t->foreignId('pembayaran_id')->unique()->constrained('pembayaran');
            $t->foreignId('tagihan_lama_id')->constrained('pembayaran');
            $t->unsignedInteger('nominal');
            $t->string('alasan', 300);
            $t->dateTime('dicatat_pada');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pemakaian_deposit');
    }
};

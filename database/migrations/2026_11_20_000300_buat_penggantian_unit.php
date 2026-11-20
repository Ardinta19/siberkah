<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penggantian_unit', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pesanan_id')->constrained('pesanan');
            $t->foreignId('item_id')->constrained('item_pesanan');
            $t->foreignId('unit_lama_id')->constrained('unit_sewa');
            $t->foreignId('unit_baru_id')->constrained('unit_sewa');
            $t->foreignId('pengguna_id')->constrained('pengguna');
            $t->uuid('kunci');
            $t->unique(['pesanan_id', 'kunci']);
            $t->string('alasan', 300);
            $t->dateTime('dicatat_pada');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('penggantian_unit');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('desain_papan', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan');
            $t->string('metode', 12);
            $t->dateTime('jadwal_serah')->nullable();
            $t->string('judul', 25)->nullable();
            $t->string('penerima', 45)->nullable();
            $t->string('pengirim', 40)->nullable();
            $t->foreignId('editor_id')->nullable()->constrained('pengguna');
            $t->dateTime('diubah_pada')->nullable();
        });
        Schema::create('blokir_unit_harian', function (Blueprint $t) {
            $t->id(); $t->foreignId('unit_sewa_id')->constrained('unit_sewa'); $t->date('tanggal');
            $t->unique(['unit_sewa_id','tanggal']);
            $t->string('alasan', 160); $t->foreignId('pengguna_id')->constrained('pengguna'); $t->dateTime('dibuat_pada');
        });
        Schema::create('perpanjangan_sewa', function (Blueprint $t) {
            $t->id(); $t->foreignId('pesanan_id')->constrained('pesanan'); $t->uuid('kunci'); $t->unique(['pesanan_id','kunci']);
            $t->unsignedTinyInteger('jam'); $t->foreignId('penyesuaian_id')->unique()->constrained('penyesuaian_pesanan');
            $t->string('alasan', 200); $t->unsignedInteger('nominal'); $t->dateTime('batas_baru');
        });
    }
    public function down(): void
    {
        foreach (['perpanjangan_sewa','blokir_unit_harian','desain_papan'] as $table) { Schema::dropIfExists($table); }
    }
};

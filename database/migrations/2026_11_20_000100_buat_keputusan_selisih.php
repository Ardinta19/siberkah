<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('keputusan_selisih', function (Blueprint $t) {
            $t->foreignId('mutasi_id')->primary()->constrained('mutasi_masuk');
            $t->foreignId('pengguna_id')->constrained('pengguna');
            $t->string('keputusan', 8);
            $t->string('catatan', 300);
            $t->dateTime('diputuskan_pada');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('keputusan_selisih');
    }
};

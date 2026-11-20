<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bukti_transfer', function (Blueprint $t) {
            $t->foreignId('pembayaran_id')->primary()->constrained('pembayaran')->restrictOnDelete();
            $t->mediumText('gambar_terenkripsi');
            $t->char('sidik', 64);
            $t->dateTime('dibuat_pada');
        });
    }

    public function down(): void { Schema::dropIfExists('bukti_transfer'); }
};

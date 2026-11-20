<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penguasaan_unit', function (Blueprint $t) {
            $t->foreignId('unit_sewa_id')->primary()->constrained('unit_sewa')->restrictOnDelete();
            $t->foreignId('pesanan_id')->constrained('pesanan')->restrictOnDelete();
        });
        Schema::table('inspeksi_sewa', fn (Blueprint $t) => $t->json('daftar_periksa')->nullable());
        Schema::create('penutupan_sewa', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan')->restrictOnDelete();
            $t->foreignId('penilai_id')->constrained('pengguna')->restrictOnDelete();
            $t->unsignedInteger('biaya_kerusakan');
            $t->string('alasan', 300);
            $t->foreignId('tagihan_id')->nullable()->unique()->constrained('pembayaran')->restrictOnDelete();
            $t->dateTime('dinilai_pada');
            $t->foreignId('penerima_setoran_id')->nullable()->constrained('pengguna')->restrictOnDelete();
            $t->dateTime('setoran_pada')->nullable();
            $t->string('catatan_penutupan', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penutupan_sewa');
        Schema::table('inspeksi_sewa', fn (Blueprint $t) => $t->dropColumn('daftar_periksa'));
        Schema::dropIfExists('penguasaan_unit');
    }
};

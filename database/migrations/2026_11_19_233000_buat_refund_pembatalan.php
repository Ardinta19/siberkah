<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keputusan_refund', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan')->restrictOnDelete();
            $t->foreignId('pengguna_id')->constrained('pengguna')->restrictOnDelete();
            $t->foreignId('pembayaran_refund_id')->nullable()->unique()->constrained('pembayaran')->restrictOnDelete();
            $t->unsignedInteger('pokok_dibayar');
            $t->unsignedInteger('nominal_sistem');
            $t->unsignedInteger('nominal_final');
            $t->string('alasan_override', 300)->nullable();
            $t->char('sidik', 64);
            $t->dateTime('diputuskan_pada');
        });
        // One outgoing bank reference cannot settle two cases, even across workflows.
        Schema::create('referensi_mutasi_keluar', function (Blueprint $t) {
            $t->string('referensi_keluar', 100)->primary();
            $t->foreignId('pembayaran_refund_id')->nullable()->unique()->constrained('pembayaran')->restrictOnDelete();
        });
        DB::table('referensi_mutasi_keluar')->insertUsing(['referensi_keluar'], DB::table('pengembalian_mutasi')->select('referensi_keluar'));
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi_mutasi_keluar');
        Schema::dropIfExists('keputusan_refund');
    }
};

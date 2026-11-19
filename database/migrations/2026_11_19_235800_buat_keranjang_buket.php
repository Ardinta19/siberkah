<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kapasitas_produksi_harian', fn (Blueprint $t) => $t->unsignedSmallInteger('terpakai')->default(0));
        Schema::table('produk', fn (Blueprint $t) => $t->unsignedTinyInteger('lead_custom_hari')->default(2));
        Schema::create('checkout_buket', function (Blueprint $t) {
            $t->foreignId('pesanan_id')->primary()->constrained('pesanan');
            $t->foreignId('pelanggan_id')->constrained('pelanggan');
            $t->uuid('kunci');
            $t->unique(['pelanggan_id', 'kunci']);
            $t->char('sidik', 64);
            $t->string('versi_privasi', 40);
            $t->dateTime('disetujui_pada');
            $t->foreignId('kapasitas_id')->nullable()->constrained('kapasitas_produksi_harian');
            $t->unsignedSmallInteger('jumlah_custom');
        });
        DB::statement('CREATE OR REPLACE VIEW checkout_bank AS SELECT pesanan_id FROM checkout_sewa UNION ALL SELECT pesanan_id FROM checkout_buket');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS checkout_bank');
        Schema::dropIfExists('checkout_buket');
        Schema::table('produk', fn (Blueprint $t) => $t->dropColumn('lead_custom_hari'));
        Schema::table('kapasitas_produksi_harian', fn (Blueprint $t) => $t->dropColumn('terpakai'));
    }
};

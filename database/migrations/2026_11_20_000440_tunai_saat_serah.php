<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', fn (Blueprint $t) => $t->boolean('tunai_saat_serah')->default(false));
        Schema::table('pembayaran', fn (Blueprint $t) => $t->boolean('tunai_saat_serah')->default(false));
    }

    public function down(): void
    {
        Schema::table('pembayaran', fn (Blueprint $t) => $t->dropColumn('tunai_saat_serah'));
        Schema::table('pesanan', fn (Blueprint $t) => $t->dropColumn('tunai_saat_serah'));
    }
};

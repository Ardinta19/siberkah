<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengguna', fn (Blueprint $table) => $table->boolean('wajib_ganti_sandi')->default(true));
    }

    public function down(): void
    {
        Schema::table('pengguna', fn (Blueprint $table) => $table->dropColumn('wajib_ganti_sandi'));
    }
};

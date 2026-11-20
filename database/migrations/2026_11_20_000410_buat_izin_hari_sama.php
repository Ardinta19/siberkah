<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produk', fn (Blueprint $t) => $t->boolean('izin_hari_sama')->default(false));
    }
    public function down(): void
    {
        Schema::table('produk', fn (Blueprint $t) => $t->dropColumn('izin_hari_sama'));
    }
};

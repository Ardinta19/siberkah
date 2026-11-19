<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->string('versi_kebijakan', 40)->nullable();
            $table->dateTime('disetujui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', fn (Blueprint $table) => $table->dropColumn(['versi_kebijakan', 'disetujui_pada']));
    }
};

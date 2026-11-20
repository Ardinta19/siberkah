<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('checkout_sewa', fn (Blueprint $t) => $t->unsignedInteger('denda_per_jam')->default(25000));
    }

    public function down(): void
    {
        Schema::table('checkout_sewa', fn (Blueprint $t) => $t->dropColumn('denda_per_jam'));
    }
};

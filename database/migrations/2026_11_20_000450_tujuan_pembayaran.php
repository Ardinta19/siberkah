<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rekening_pembayaran', function (Blueprint $t) {
            $t->string('jenis', 8)->default('bank');
            $t->mediumText('qris_png')->nullable();
        });
    }

    public function down(): void
    {
        // Keep QRIS records for referenced invoices, but never expose them as bank accounts.
        DB::table('rekening_pembayaran')->where('jenis', 'qris')->update(['aktif' => false]);
        Schema::table('rekening_pembayaran', fn (Blueprint $t) => $t->dropColumn(['jenis','qris_png']));
    }
};

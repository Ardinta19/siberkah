<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }
        $account = DB::table('pengguna')->where('surel', 'test@example.com')->where('peran', 'owner')->first();
        if ($account && password_verify('password', $account->kata_sandi)) {
            DB::table('pengguna')->where('id', $account->id)->update(['aktif' => false, 'remember_token' => null]);
        }
    }

    public function down(): void
    {
        // Jangan aktifkan ulang kredensial demo saat rollback.
    }
};

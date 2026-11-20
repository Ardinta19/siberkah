<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class StaffAudit
{
    /** Call inside the mutation transaction. Only pass business fields, never credentials or file contents. */
    public static function record(string $action, string $table, int $id, ?array $before, array $after): void
    {
        if ($before === $after) { return; }
        DB::table('log_aktivitas')->insert(['pengguna_id' => auth('staff')->id(), 'aksi' => $action, 'subjek_tabel' => $table,
            'subjek_id' => $id, 'sebelum' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'sesudah' => json_encode($after, JSON_THROW_ON_ERROR), 'dibuat_pada' => now()]);
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class BusinessSettings
{
    /** default, minimum, maximum; units are part of the key */
    public const RULES = [
        'tahan_normal_menit' => [60, 1, 1440],
        'tahan_puncak_menit' => [30, 1, 1440],
        'ambang_dp_rupiah' => [250000, 1000, 2147482000],
        'retensi_transaksi_tahun' => [3, 1, 10],
    ];

    public static function value(string $key): int
    {
        [$default, $min, $max] = self::RULES[$key];
        $stored = DB::table('pengaturan')->where('kunci', $key)->value('nilai');
        if ($stored === null) {
            return $default;
        }
        abort_unless(ctype_digit((string) $stored) && $stored >= $min && $stored <= $max, 503, 'Periksa pengaturan bisnis: '.$key);
        return (int) $stored;
    }
}

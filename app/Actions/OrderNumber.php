<?php

namespace App\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;

/** Allocate inside the checkout transaction. */
final class OrderNumber
{
    public function next(string $period): string
    {
        // MariaDB: acquire an exclusive counter lock directly (no shared-lock upgrade).
        DB::statement('INSERT INTO urutan_nomor_pesanan (periode, terakhir) VALUES (?, 1) ON DUPLICATE KEY UPDATE terakhir = terakhir + 1', [$period]);
        $n = DB::table('urutan_nomor_pesanan')->where('periode', $period)->lockForUpdate()->value('terakhir');
        if ($n > 9999) {
            throw new DomainException('Nomor pesanan bulan ini habis.');
        }
        return sprintf('BK-%s-%04d', $period, $n);
    }
}

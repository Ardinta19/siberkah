<?php

namespace App\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;

/** Allocate inside the checkout transaction. */
final class OrderNumber
{
    public function next(string $period): string
    {
        $counter = DB::table('urutan_nomor_pesanan');
        $counter->insertOrIgnore(['periode' => $period, 'terakhir' => 0]);
        $row = $counter->where('periode', $period)->lockForUpdate()->first();
        $n = $row->terakhir + 1;
        if ($n > 9999) {
            throw new DomainException('Nomor pesanan bulan ini habis.');
        }
        $counter->update(['terakhir' => $n]);
        return sprintf('BK-%s-%04d', $period, $n);
    }
}

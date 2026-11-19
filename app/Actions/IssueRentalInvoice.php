<?php

namespace App\Actions;

use DateTimeInterface;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Caller owns the order transaction and lock. */
final class IssueRentalInvoice
{
    public function issue(int $order, string $type, int $amount, DateTimeInterface $now): void
    {
        $taken = DB::table('pembayaran')->whereBetween('nominal_tertunda', [$amount + 1, $amount + 999])->pluck('nominal_tertunda')->flip();
        // Do not recycle this order's previous transfer total for its next invoice.
        $previous = DB::table('pembayaran')->where('pesanan_id', $order)->pluck('nominal_diharapkan')->flip();
        $start = random_int(0, 998);
        for ($i = 0; $i < 999; $i++) {
            $code = 1 + (($start + $i) % 999);
            $expected = $amount + $code;
            if (! $taken->has($expected) && ! $previous->has($expected)) {
                try {
                    DB::table('pembayaran')->insert(['pesanan_id' => $order, 'jenis' => $type, 'status' => 'menunggu', 'tagihan' => $amount,
                    'kode_unik' => $code, 'nominal_diharapkan' => $expected, 'nominal_tertunda' => $expected, 'dibuat_pada' => $now, 'diubah_pada' => $now]);
                } catch (UniqueConstraintViolationException) {
                    // A different invoice writer claimed the same pending amount.
                    continue;
                }
                return;
            }
        }
        throw new DomainException('Semua kode pembayaran untuk nominal ini sedang digunakan.');
    }
}

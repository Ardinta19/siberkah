<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

/** Remove per operation only after the persistence and authorization tests pass. */
class PendingTransaction
{
    public function handle(Request $request, Closure $next): never
    {
        abort(503, 'Layanan transaksi belum tersedia. Tidak ada transaksi yang diproses.');
    }
}

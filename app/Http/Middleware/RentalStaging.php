<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RentalStaging
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Layanan sewa belum dibuka.');
        return $next($request);
    }
}

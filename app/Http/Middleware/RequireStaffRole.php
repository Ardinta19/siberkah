<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStaffRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $staff = $request->user('staff');
        abort_unless($staff?->aktif && in_array($staff->peran, $roles, true), 403);

        return $next($request);
    }
}

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

        if ($staff->wajib_ganti_sandi && ! $request->routeIs('staff.password.form', 'staff.password.change')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Ganti kata sandi sebelum melanjutkan.'], 403)
                : redirect()->route('staff.password.form');
        }

        return $next($request);
    }
}

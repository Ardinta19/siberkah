<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.customer' => \App\Http\Middleware\RequireActiveCustomer::class,
            'role' => \App\Http\Middleware\RequireStaffRole::class,
            'pending.transaction' => \App\Http\Middleware\PendingTransaction::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('staff/*', 'staf/*', 'admin/*', 'owner')
            ? route('staff.login') : route('otp.form'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['kode', 'new_password', 'new_password_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
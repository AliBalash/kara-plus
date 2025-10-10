<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'auth.check' => \App\Http\Middleware\EnsureAuthenticated::class,
            'auth.guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'restrict.driver' => \App\Http\Middleware\RestrictDriverAccess::class,

        ]);

        // $middleware->validateCsrfTokens(except: [
        // 	'*',
        // 	'/*',
        // 	'stripe/*',
        // 	'http://127.0.0.1:8000/reserve-car',
        // ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

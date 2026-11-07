<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'admin.access' => \App\Http\Middleware\AdminAccess::class,
        ]);
        $middleware->validateCsrfTokens(except: ['payment/webhook']);
        $middleware->web(append: [\App\Http\Middleware\TrackPageView::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

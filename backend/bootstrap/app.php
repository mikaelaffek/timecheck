<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Providers\AuthServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Register the CORS middleware for all routes
        $middleware->alias([
            'cors' => \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        $middleware->prependToGroup('api', 'cors');
        $middleware->prependToGroup('web', 'cors');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

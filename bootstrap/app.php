<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
        // All default Laravel middleware from Kernel.php are now registered automatically
        // TrustProxies, PreventRequestsDuringMaintenance, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Migrate custom rendering from app/Exceptions/Handler.php
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Please login again.',
                ], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found.',
                ], 404);
            }
        });
    })->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponseMiddleware::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\LogRequestMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'error' => 'Resource Not Found',
                'message' => 'The requested resource could not be found.'
            ], 404);
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Unauthenticated or invalid token.'
            ], 401);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'The requested endpoint does not exist.'
            ], 404);
        });
    })->create();

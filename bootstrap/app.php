<?php

use App\Helpers\ApiResponse;
use App\Http\Middleware\GuestApi;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'guest.api' => GuestApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    $e->getMessage(),
                    422,
                    $e->errors()
                );
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    return ApiResponse::error('Resource not found.', 404);
                }

                if ($e instanceof TypeError && str_contains($e->getMessage(), 'must be of type int, string given')) {
                    return ApiResponse::error('Resource not found.', 404);
                }

                if ($e instanceof AuthenticationException) {
                    return ApiResponse::error($e->getMessage(), 401);
                }

                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                return ApiResponse::error(
                    $e->getMessage(),
                    $statusCode
                );
            }
        });

    })->create();

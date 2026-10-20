<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $payload = match (true) {
                $e instanceof ValidationException => [
                    'message' => 'Validation failed.',
                    'status' => 422,
                    'errors' => $e->errors(),
                ],
                $e instanceof TokenExpiredException => [
                    'message' => 'Token has expired.',
                    'status' => 401,
                ],
                $e instanceof TokenInvalidException => [
                    'message' => 'Token is invalid.',
                    'status' => 401,
                ],
                $e instanceof JWTException => [
                    'message' => 'Token is absent or could not be parsed.',
                    'status' => 401,
                ],
                $e instanceof AuthenticationException => [
                    'message' => 'Unauthenticated.',
                    'status' => 401,
                ],
                $e instanceof UnauthorizedHttpException => [
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'status' => 401,
                ],
                default => null,
            };

            if ($payload === null) {
                return null;
            }

            $body = [
                'success' => false,
                'message' => $payload['message'],
            ];

            if (isset($payload['errors'])) {
                $body['errors'] = $payload['errors'];
            }

            return response()->json($body, $payload['status']);
        });
    })->create();

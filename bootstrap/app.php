<?php

$applicationClass = 'Illuminate'.chr(92).'Foundation'.chr(92).'Application';

return $applicationClass::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up', apiPrefix: 'api')
    ->withMiddleware(function ($middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? '/' : '/');
    })
    ->withExceptions(function ($exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') && is_a($e, 'Illuminate'.chr(92).'Validation'.chr(92).'ValidationException')) {
                return response()->json(['message' => 'Dữ liệu không hợp lệ.', 'errors' => $e->errors()], 422);
            }
        });
    })->create();

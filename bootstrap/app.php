<?php

use App\Http\Middleware\ActiveAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['active' => ActiveAccount::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Vui lòng đăng nhập.'], 401);
            }
        });
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Dữ liệu không hợp lệ.', 'errors' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = $e->getStatusCode();
            $messages = [401 => 'Vui lòng đăng nhập.', 403 => 'Bạn không có quyền thực hiện thao tác này.', 404 => 'Không tìm thấy dữ liệu.', 409 => 'Dữ liệu đã thay đổi. Vui lòng tải lại hồ sơ.', 419 => 'Phiên đã hết hạn. Vui lòng đăng nhập lại.', 429 => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.'];
            $message = $e->getMessage();
            if ($status === 404 || ! $message || in_array($message, ['This action is unauthorized.', 'Not Found', 'CSRF token mismatch.', 'Too Many Attempts.'], true)) {
                $message = $messages[$status] ?? 'Không thể thực hiện thao tác.';
            }

            return response()->json(['message' => $message], $status, $e->getHeaders());
        });
    })->create();

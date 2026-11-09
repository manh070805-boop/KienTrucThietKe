<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class ActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_active, 403, 'Tài khoản đã ngừng hoạt động.');

        return $next($request);
    }
}

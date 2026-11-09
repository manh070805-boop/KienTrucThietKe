<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketStatusHistory;
use App\Policies\TicketPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(mb_strtolower((string) $r->input('email')).'|'.$r->ip()));
        foreach ([TicketStatusHistory::class, TicketAssignment::class, AuditLog::class] as $class) {
            $class::updating(fn () => throw new \LogicException('Nhật ký chỉ được ghi thêm.'));
            $class::deleting(fn () => throw new \LogicException('Không được xóa nhật ký nghiệp vụ.'));
        }
    }
}

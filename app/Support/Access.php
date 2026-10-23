<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;

final class Access
{
    public static function role(User $user, string ...$roles): bool
    {
        return $user->is_active && $user->roles()->whereIn('code', $roles)->exists();
    }

    public static function canViewTicket(User $user, Ticket $ticket): bool
    {
        if (!$user->is_active) return false;
        if ((int) $ticket->student_id === (int) $user->id) return true;
        if (self::role($user, 'COORDINATOR')) return $ticket->status?->value !== 'DRAFT';
        return self::role($user, 'STAFF') && (int) $ticket->assigned_staff_id === (int) $user->id && $ticket->status?->value !== 'DRAFT';
    }

    public static function canSeeInternal(User $user, Ticket $ticket): bool
    {
        return self::role($user, 'COORDINATOR') || (self::role($user, 'STAFF') && (int) $ticket->assigned_staff_id === (int) $user->id);
    }
}

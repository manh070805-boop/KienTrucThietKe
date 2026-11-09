<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class Access
{
    public static function role(User $user, string ...$roles): bool
    {
        return $user->is_active && $user->roles->contains(fn ($role) => in_array($role->code, $roles, true));
    }

    public static function owner(User $user, Ticket $ticket): bool
    {
        return self::role($user, 'STUDENT') && (int) $ticket->student_id === (int) $user->id;
    }

    public static function staff(User $user, Ticket $ticket): bool
    {
        if (! self::role($user, 'STAFF') || (int) $ticket->assigned_staff_id !== (int) $user->id) {
            return false;
        }
        $user->loadMissing('departmentMemberships.department');

        return $user->departmentMemberships->contains(fn ($member) => (int) $member->department_id === (int) $ticket->assigned_department_id && $member->is_active && $member->department->is_active);
    }

    public static function scope(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->whereRaw('1 = 0');
            if (self::role($user, 'STUDENT')) {
                $q->orWhere('student_id', $user->id);
            }
            if (self::role($user, 'COORDINATOR')) {
                $q->orWhere('status', '!=', 'DRAFT');
            }
            if (self::role($user, 'STAFF')) {
                $departments = $user->departmentMemberships()->where('is_active', true)
                    ->whereHas('department', fn ($d) => $d->where('is_active', true))->select('department_id');
                $q->orWhere(fn ($s) => $s->where('assigned_staff_id', $user->id)->whereIn('assigned_department_id', $departments)->where('status', '!=', 'DRAFT'));
            }
        });
    }

    public static function canViewTicket(User $user, Ticket $ticket): bool
    {
        return self::owner($user, $ticket) || ($ticket->status->value !== 'DRAFT'
            && (self::role($user, 'COORDINATOR') || self::staff($user, $ticket)));
    }

    public static function canSeeInternal(User $user, Ticket $ticket): bool
    {
        return $ticket->status->value !== 'DRAFT' && (self::role($user, 'COORDINATOR') || self::staff($user, $ticket));
    }
}

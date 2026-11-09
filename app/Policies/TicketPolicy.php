<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Support\Access;

final class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return Access::canViewTicket($user, $ticket);
    }

    public function create(User $user): bool
    {
        return Access::role($user, 'STUDENT');
    }

    public function act(User $user, Ticket $ticket, string $action): bool
    {
        return match ($action) {
            'update', 'submit', 'cancel', 'close', 'reopen', 'rate' => Access::owner($user, $ticket),
            'assign', 'transfer', 'classify', 'reject' => Access::role($user, 'COORDINATOR') && $ticket->status->value !== 'DRAFT',
            'start', 'request-information', 'resolve' => Access::staff($user, $ticket),
            'internal-note' => Access::canSeeInternal($user, $ticket),
            'messages' => Access::canViewTicket($user, $ticket),
            default => false,
        };
    }
}

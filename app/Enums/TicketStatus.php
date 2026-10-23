<?php

namespace App\Enums;

enum TicketStatus: string
{
    case DRAFT = 'DRAFT'; case SUBMITTED = 'SUBMITTED'; case ASSIGNED = 'ASSIGNED'; case IN_PROGRESS = 'IN_PROGRESS'; case WAITING_STUDENT = 'WAITING_STUDENT'; case RESOLVED = 'RESOLVED'; case CLOSED = 'CLOSED'; case REJECTED = 'REJECTED'; case CANCELLED = 'CANCELLED';
    public function isOpen(): bool { return in_array($this, [self::SUBMITTED, self::ASSIGNED, self::IN_PROGRESS, self::WAITING_STUDENT], true); }
}

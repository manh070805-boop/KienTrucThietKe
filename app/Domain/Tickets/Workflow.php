<?php

namespace App\Domain\Tickets;

final class Workflow
{
    public const TRANSITIONS = [
        'submit' => ['DRAFT', 'SUBMITTED'], 'assign' => ['SUBMITTED', 'ASSIGNED'],
        'start' => ['ASSIGNED', 'IN_PROGRESS'], 'request-information' => ['IN_PROGRESS', 'WAITING_STUDENT'],
        'resolve' => ['IN_PROGRESS', 'RESOLVED'], 'reopen' => ['RESOLVED', 'IN_PROGRESS'],
        'close' => ['RESOLVED', 'CLOSED'], 'reject' => ['SUBMITTED', 'REJECTED'], 'cancel' => ['SUBMITTED', 'CANCELLED'],
    ];

    public const OPEN = ['SUBMITTED', 'ASSIGNED', 'IN_PROGRESS', 'WAITING_STUDENT'];

    public static function target(string $action, string $status): ?string
    {
        if ($action === 'transfer') {
            abort_unless(in_array($status, ['ASSIGNED', 'IN_PROGRESS'], true), 409, 'Chỉ chuyển hồ sơ đã phân công hoặc đang xử lý.');

            return 'ASSIGNED';
        }
        if (! isset(self::TRANSITIONS[$action])) {
            return null;
        }
        [$from, $to] = self::TRANSITIONS[$action];
        abort_unless($status === $from, 409, 'Trạng thái hiện tại không cho phép thao tác này.');

        return $to;
    }
}

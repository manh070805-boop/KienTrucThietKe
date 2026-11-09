<?php

namespace App\Services;

use App\Domain\Tickets\Workflow;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\DepartmentMember;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketMessage;
use App\Models\TicketRating;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\Access;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TicketService
{
    public function __construct(private TicketPolicy $policy) {}

    public function create(User $user, array $data): Ticket
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->policy->create($user), 403);
            $existing = Ticket::where('student_id', $user->id)->where('client_request_id', $data['client_request_id'])->first();
            if ($existing) {
                return $existing;
            }
            $ticket = Ticket::forceCreate($data + ['student_id' => $user->id, 'status' => 'DRAFT', 'version' => 1]);
            $this->history($ticket, null, 'DRAFT', $user);
            $this->audit($ticket, $user, 'create', []);

            return $ticket;
        }, 3);
    }

    public function execute(User $user, Ticket $ticket, string $action, array $data): Ticket
    {
        return DB::transaction(function () use ($user, $ticket, $action, $data) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $user = User::findOrFail($user->id);
            abort_unless($this->policy->act($user, $locked, $action), 403);
            $from = $locked->status->value;
            // The persisted pre-submit version identifies a lost-response retry, even if processing has advanced.
            if ($action === 'submit' && $from !== 'DRAFT' && $locked->submitted_version !== null && (int) $locked->submitted_version === (int) $data['version']) {
                return $locked;
            }
            $this->assertVersion($locked, (int) $data['version']);
            $target = Workflow::target($action, $from);
            $metadata = [];

            switch ($action) {
                case 'update':
                    abort_unless($from === 'DRAFT', 409, 'Nội dung gốc chỉ được sửa khi còn nháp.');
                    $locked->fill(Arr::only($data, ['type', 'title', 'description', 'category_id']));
                    break;
                case 'submit':
                    $category = Category::whereKey($locked->category_id)->where('is_active', true)
                        ->whereHas('department', fn ($q) => $q->where('is_active', true))->sharedLock()->first();
                    $errors = [];
                    if (! trim($locked->title ?? '')) {
                        $errors['title'] = ['Vui lòng lưu tiêu đề trước khi gửi.'];
                    }
                    if (! trim($locked->description ?? '')) {
                        $errors['description'] = ['Vui lòng lưu nội dung trước khi gửi.'];
                    }
                    if (! $category) {
                        $errors['category_id'] = ['Vui lòng chọn danh mục và đơn vị đang hoạt động.'];
                    }
                    if ($errors) {
                        throw ValidationException::withMessages($errors);
                    }
                    $locked->code = 'SV-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(6)));
                    $locked->submitted_at = now();
                    $locked->due_at = now()->addDays($category->resolution_days ?: 7);
                    $locked->submitted_version = $locked->version;
                    break;
                case 'assign':
                case 'transfer':
                    $member = DepartmentMember::where('department_id', $data['department_id'])->where('user_id', $data['staff_id'])
                        ->where('is_active', true)->whereHas('department', fn ($q) => $q->where('is_active', true))
                        ->whereHas('user', fn ($q) => $q->where('is_active', true)->whereHas('roles', fn ($r) => $r->where('code', 'STAFF')))
                        ->sharedLock()->first();
                    if (! $member) {
                        throw ValidationException::withMessages(['staff_id' => ['Cán bộ phải có vai trò STAFF, tài khoản, đơn vị và thành viên đang hoạt động.']]);
                    }
                    if ($action === 'transfer' && (int) $locked->assigned_department_id === (int) $data['department_id'] && (int) $locked->assigned_staff_id === (int) $data['staff_id']) {
                        throw ValidationException::withMessages(['staff_id' => ['Hãy chọn người phụ trách khác.']]);
                    }
                    TicketAssignment::create([
                        'ticket_id' => $locked->id, 'from_department_id' => $locked->assigned_department_id, 'from_staff_id' => $locked->assigned_staff_id,
                        'to_department_id' => $data['department_id'], 'to_staff_id' => $data['staff_id'], 'assigned_by' => $user->id, 'reason' => $data['reason'] ?? null,
                    ]);
                    $locked->assigned_department_id = $data['department_id'];
                    $locked->assigned_staff_id = $data['staff_id'];
                    $metadata = Arr::only($data, ['department_id', 'staff_id']);
                    break;
                case 'classify':
                    abort_unless(! in_array($from, ['DRAFT', 'CLOSED', 'REJECTED', 'CANCELLED'], true), 409);
                    $category = Category::whereKey($data['category_id'])->where('is_active', true)->first();
                    if (! $category) {
                        throw ValidationException::withMessages(['category_id' => ['Danh mục đã ngừng hoạt động.']]);
                    }
                    $metadata = ['old_category_id' => $locked->category_id, 'category_id' => $category->id];
                    $locked->category_id = $category->id;
                    break;
                case 'messages':
                case 'internal-note':
                case 'request-information':
                case 'resolve':
                    abort_unless(! in_array($from, ['DRAFT', 'CLOSED', 'CANCELLED', 'REJECTED'], true), 409, 'Hồ sơ không còn nhận trao đổi.');
                    $kind = match ($action) {
                        'request-information' => 'INFO_REQUEST', 'resolve' => 'RESOLUTION',
                        default => $action === 'messages' && Access::owner($user, $locked) && $from === 'WAITING_STUDENT' ? 'INFO_RESPONSE' : 'COMMENT',
                    };
                    TicketMessage::create(['ticket_id' => $locked->id, 'author_id' => $user->id, 'kind' => $kind,
                        'visibility' => $action === 'internal-note' ? 'STAFF_ONLY' : 'STUDENT_VISIBLE', 'body' => $data['body']]);
                    if ($kind === 'INFO_RESPONSE') {
                        $target = 'IN_PROGRESS';
                    }
                    if ($action === 'resolve') {
                        $locked->resolved_at = now();
                    }
                    break;
                case 'reopen':
                    $locked->resolved_at = null;
                    TicketMessage::create(['ticket_id' => $locked->id, 'author_id' => $user->id, 'kind' => 'COMMENT', 'visibility' => 'STUDENT_VISIBLE', 'body' => 'Yêu cầu xử lý lại: '.$data['reason']]);
                    break;
                case 'close': $locked->closed_at = now();
                    break;
                case 'rate':
                    abort_unless($from === 'CLOSED', 409, 'Chỉ đánh giá sau khi đóng hồ sơ.');
                    abort_if($locked->rating()->exists(), 409, 'Hồ sơ chỉ được đánh giá một lần.');
                    TicketRating::create(['ticket_id' => $locked->id, 'score' => $data['score'], 'comment' => $data['comment'] ?? null]);
                    break;
            }
            $locked->version++;
            if ($target) {
                $locked->status = $target;
            }
            $locked->save();
            if ($target) {
                $this->history($locked, $from, $target, $user, $data['reason'] ?? null);
            }
            $this->audit($locked, $user, $action, $metadata);
            $this->notify($locked, $user, $action);

            return $locked;
        }, 3);
    }

    public function assertVersion(Ticket $ticket, int $version): void
    {
        abort_unless($ticket->version === $version, 409, 'Dữ liệu đã thay đổi. Tải lại hồ sơ trước khi thử lại; nội dung đang nhập vẫn được giữ.');
    }

    private function history(Ticket $ticket, ?string $from, string $to, User $user, ?string $reason = null): void
    {
        TicketStatusHistory::create(['ticket_id' => $ticket->id, 'from_status' => $from, 'to_status' => $to, 'changed_by' => $user->id, 'reason' => $reason]);
    }

    private function audit(Ticket $ticket, User $user, string $action, array $metadata): void
    {
        AuditLog::create(['actor_id' => $user->id, 'action' => 'ticket.'.$action, 'subject_type' => 'Ticket', 'subject_id' => $ticket->id, 'metadata' => $metadata + ['version' => $ticket->version]]);
    }

    private function notify(Ticket $ticket, User $actor, string $action): void
    {
        if (in_array($action, ['update', 'rate', 'classify', 'internal-note'], true)) {
            return;
        }
        $ids = collect([$ticket->student_id, $ticket->assigned_staff_id]);
        if ($action === 'submit') {
            $ids = $ids->merge(User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('code', 'COORDINATOR'))->pluck('id'));
        }
        foreach ($ids->filter()->unique()->reject(fn ($id) => (int) $id === (int) $actor->id) as $id) {
            Notification::create(['user_id' => $id, 'ticket_id' => $ticket->id, 'event_type' => $action, 'title' => 'Hồ sơ '.$ticket->code.' có cập nhật', 'body' => 'Mở hồ sơ để xem tiến độ và nội dung được phép.']);
        }
    }
}

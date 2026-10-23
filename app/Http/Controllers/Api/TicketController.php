<?php

namespace App\Http\Controllers\Api;

use App\Enums\MessageKind;
use App\Enums\MessageVisibility;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\DepartmentMember;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketMessage;
use App\Models\TicketRating;
use App\Models\TicketStatusHistory;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['student:id,full_name,name,email,student_code', 'category:id,name', 'assignedDepartment:id,name', 'assignedStaff:id,full_name,name'])
            ->when(Access::role($user, 'STUDENT'), fn (Builder $q) => $q->where('student_id', $user->id))
            ->when(Access::role($user, 'STAFF') && !Access::role($user, 'COORDINATOR'), fn (Builder $q) => $q->where('assigned_staff_id', $user->id))
            ->when(Access::role($user, 'COORDINATOR'), fn (Builder $q) => $q->where('status', '!=', TicketStatus::DRAFT->value));
        $query->when($request->filled('status'), fn (Builder $q) => $q->whereIn('status', array_intersect((array) $request->input('status'), array_column(TicketStatus::cases(), 'value'))));
        $query->when($request->filled('type'), fn (Builder $q) => $q->whereIn('type', ['FEEDBACK', 'PROPOSAL']));
        $sort = in_array($request->input('sort'), ['created_at', 'due_at', 'updated_at', 'status'], true) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $pageSize = min(max((int) $request->input('per_page', 15), 1), 50);
        return response()->json($query->orderBy($sort, $direction)->orderBy('id', 'desc')->paginate($pageSize));
    }

    public function store(Request $request)
    {
        abort_unless(Access::role($request->user(), 'STUDENT'), 403, 'Chỉ sinh viên được tạo hồ sơ.');
        $data = $request->validate(['client_request_id' => ['required', 'uuid'], 'type' => ['required', Rule::in(['FEEDBACK', 'PROPOSAL'])]]);
        $ticket = Ticket::firstOrCreate(['student_id' => $request->user()->id, 'client_request_id' => $data['client_request_id']], ['type' => $data['type'], 'status' => TicketStatus::DRAFT->value, 'version' => 1]);
        return response()->json(['data' => $this->serializeTicket($ticket->load('category'))], $ticket->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless(Access::canViewTicket($request->user(), $ticket), 403, 'Bạn không có quyền xem hồ sơ này.');
        return response()->json(['data' => $this->serializeTicket($ticket->load(['student', 'category', 'assignedDepartment', 'assignedStaff', 'attachments', 'statusHistory.changer', 'assignments.toDepartment', 'assignments.toStaff', 'rating']))]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id && $ticket->status === TicketStatus::DRAFT, 403, 'Chỉ chủ hồ sơ được sửa bản nháp.');
        $this->assertVersion($request, $ticket);
        $data = $request->validate(['type' => ['sometimes', Rule::in(['FEEDBACK', 'PROPOSAL'])], 'title' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'category_id' => ['nullable', 'integer', 'exists:categories,id']]);
        $ticket->update($data + ['version' => $ticket->version + 1]);
        return response()->json(['data' => $this->serializeTicket($ticket->fresh('category'))]);
    }

    public function submit(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id, 403);
        $this->assertVersion($request, $ticket);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:10000'], 'category_id' => ['required', 'integer', 'exists:categories,id']]);
        $result = DB::transaction(function () use ($request, $ticket, $data) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === TicketStatus::SUBMITTED) return $locked;
            abort_unless($locked->status === TicketStatus::DRAFT, 409, 'Hồ sơ không còn ở trạng thái bản nháp.');
            $category = Category::whereKey($data['category_id'])->where('is_active', true)->firstOrFail();
            $locked->fill(['title' => $data['title'], 'description' => $data['description'], 'category_id' => $category->id, 'status' => TicketStatus::SUBMITTED, 'code' => $this->newCode(), 'submitted_at' => now(), 'due_at' => now()->addDays($category->resolution_days), 'version' => $locked->version + 1])->save();
            $this->history($locked, TicketStatus::DRAFT, TicketStatus::SUBMITTED, $request->user()->id);
            $this->notify($locked->student_id, $locked, 'SUBMITTED', 'Đã tiếp nhận hồ sơ', 'Hồ sơ '.$locked->code.' đã được gửi.');
            return $locked;
        });
        return response()->json(['data' => $this->serializeTicket($result->fresh())]);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        abort_unless(Access::role($request->user(), 'COORDINATOR'), 403);
        $data = $request->validate(['department_id' => ['required', 'integer', 'exists:departments,id'], 'staff_id' => ['required', 'integer', 'exists:users,id'], 'reason' => ['nullable', 'string', 'max:500'], 'version' => ['nullable', 'integer']]);
        $result = DB::transaction(function () use ($request, $ticket, $data) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail(); $this->assertVersion($request, $locked);
            abort_unless(in_array($locked->status, [TicketStatus::SUBMITTED, TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS], true), 409, 'Trạng thái không cho phép phân công.');
            $member = DepartmentMember::where('department_id', $data['department_id'])->where('user_id', $data['staff_id'])->where('is_active', true)->whereHas('user', fn ($q) => $q->where('is_active', true)->whereHas('roles', fn ($r) => $r->where('code', 'STAFF')))->first();
            abort_unless($member, 422, 'Cán bộ không thuộc đơn vị hoạt động hoặc chưa có vai trò STAFF.');
            $oldStatus = $locked->status; $fromDept = $locked->assigned_department_id; $fromStaff = $locked->assigned_staff_id;
            $locked->update(['assigned_department_id' => $data['department_id'], 'assigned_staff_id' => $data['staff_id'], 'status' => TicketStatus::ASSIGNED, 'version' => $locked->version + 1]);
            TicketAssignment::create(['ticket_id' => $locked->id, 'from_department_id' => $fromDept, 'from_staff_id' => $fromStaff, 'to_department_id' => $data['department_id'], 'to_staff_id' => $data['staff_id'], 'assigned_by' => $request->user()->id, 'reason' => $data['reason']]);
            if ($oldStatus !== TicketStatus::ASSIGNED) $this->history($locked, $oldStatus, TicketStatus::ASSIGNED, $request->user()->id, $data['reason']);
            $this->notify($data['staff_id'], $locked, 'ASSIGNED', 'Hồ sơ mới được phân công', 'Bạn được phân công hồ sơ '.$locked->code.'.'); return $locked;
        });
        return response()->json(['data' => $this->serializeTicket($result->fresh())]);
    }

    public function start(Request $request, Ticket $ticket) { return $this->transition($request, $ticket, TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS, fn () => true); }

    public function requestInformation(Request $request, Ticket $ticket)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'version' => ['nullable', 'integer']]);
        return $this->messageAndTransition($request, $ticket, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_STUDENT, MessageKind::INFO_REQUEST, MessageVisibility::STUDENT_VISIBLE, $data['body']);
    }

    public function message(Request $request, Ticket $ticket)
    {
        abort_unless(Access::canViewTicket($request->user(), $ticket), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $staff = Access::role($request->user(), 'STAFF') && (int) $ticket->assigned_staff_id === (int) $request->user()->id;
        $student = (int) $ticket->student_id === (int) $request->user()->id;
        abort_unless($staff || $student, 403);
        $message = TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => $request->user()->id, 'kind' => $student ? MessageKind::INFO_RESPONSE : MessageKind::COMMENT, 'visibility' => $student ? MessageVisibility::STUDENT_VISIBLE : MessageVisibility::STUDENT_VISIBLE, 'body' => $data['body']]);
        if ($student && $ticket->status === TicketStatus::WAITING_STUDENT) $this->transition($request, $ticket, TicketStatus::WAITING_STUDENT, TicketStatus::IN_PROGRESS, fn () => true);
        return response()->json(['data' => $message]);
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'version' => ['nullable', 'integer']]);
        return $this->messageAndTransition($request, $ticket, TicketStatus::IN_PROGRESS, TicketStatus::RESOLVED, MessageKind::RESOLUTION, MessageVisibility::STUDENT_VISIBLE, $data['body']);
    }

    public function reopen(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'version' => ['nullable', 'integer']]);
        return $this->transition($request, $ticket, TicketStatus::RESOLVED, TicketStatus::IN_PROGRESS, fn ($locked) => $locked->update(['resolved_at' => null]), $data['reason']);
    }

    public function close(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id, 403);
        return $this->transition($request, $ticket, TicketStatus::RESOLVED, TicketStatus::CLOSED, fn ($locked) => $locked->update(['closed_at' => now()]));
    }

    public function reject(Request $request, Ticket $ticket)
    {
        abort_unless(Access::role($request->user(), 'COORDINATOR'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'version' => ['nullable', 'integer']]);
        return $this->transition($request, $ticket, TicketStatus::SUBMITTED, TicketStatus::REJECTED, fn () => true, $data['reason']);
    }

    public function cancel(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id, 403);
        return $this->transition($request, $ticket, TicketStatus::SUBMITTED, TicketStatus::CANCELLED, fn () => true, $request->input('reason'));
    }

    public function rate(Request $request, Ticket $ticket)
    {
        abort_unless((int) $ticket->student_id === (int) $request->user()->id && $ticket->status === TicketStatus::CLOSED, 403);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:1000']]);
        abort_if($ticket->rating()->exists(), 409, 'Hồ sơ chỉ được đánh giá một lần.');
        return response()->json(['data' => TicketRating::create(['ticket_id' => $ticket->id] + $data)], 201);
    }

    private function transition(Request $request, Ticket $ticket, TicketStatus $from, TicketStatus $to, callable $extra, ?string $reason = null)
    {
        $result = DB::transaction(function () use ($request, $ticket, $from, $to, $extra, $reason) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail(); $this->assertVersion($request, $locked);
            abort_unless($locked->status === $from, 409, 'Trạng thái hiện tại không phù hợp.');
            if ($to !== TicketStatus::CLOSED && $to !== TicketStatus::REJECTED && $to !== TicketStatus::CANCELLED) abort_unless((int) $locked->assigned_staff_id === (int) $request->user()->id || Access::role($request->user(), 'COORDINATOR') || (int) $locked->student_id === (int) $request->user()->id, 403);
            $extra($locked); $locked->update(['status' => $to, 'version' => $locked->version + 1]); $this->history($locked, $from, $to, $request->user()->id, $reason); return $locked;
        });
        return response()->json(['data' => $this->serializeTicket($result->fresh())]);
    }

    private function messageAndTransition(Request $request, Ticket $ticket, TicketStatus $from, TicketStatus $to, MessageKind $kind, MessageVisibility $visibility, string $body)
    {
        $result = DB::transaction(function () use ($request, $ticket, $from, $to, $kind, $visibility, $body) {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail(); $this->assertVersion($request, $locked);
            abort_unless($locked->status === $from && (int) $locked->assigned_staff_id === (int) $request->user()->id, 403, 'Bạn không phụ trách hồ sơ này.');
            TicketMessage::create(['ticket_id' => $locked->id, 'author_id' => $request->user()->id, 'kind' => $kind, 'visibility' => $visibility, 'body' => $body]); $locked->update(['status' => $to, 'resolved_at' => $to === TicketStatus::RESOLVED ? now() : null, 'version' => $locked->version + 1]); $this->history($locked, $from, $to, $request->user()->id); $this->notify($locked->student_id, $locked, $kind->value, 'Cập nhật hồ sơ', 'Hồ sơ '.$locked->code.' có cập nhật mới.'); return $locked;
        });
        return response()->json(['data' => $this->serializeTicket($result->fresh())]);
    }

    private function assertVersion(Request $request, Ticket $ticket): void
    {
        if ($request->filled('version') && (int) $request->input('version') !== (int) $ticket->version) abort(409, 'Dữ liệu đã thay đổi. Vui lòng tải lại hồ sơ.');
    }

    private function history(Ticket $ticket, ?TicketStatus $from, TicketStatus $to, int $userId, ?string $reason = null): void
    {
        TicketStatusHistory::create(['ticket_id' => $ticket->id, 'from_status' => $from?->value, 'to_status' => $to->value, 'changed_by' => $userId, 'reason' => $reason]);
    }

    private function notify(int $userId, Ticket $ticket, string $event, string $title, string $body): void
    {
        Notification::create(['user_id' => $userId, 'ticket_id' => $ticket->id, 'event_type' => $event, 'title' => $title, 'body' => $body]);
    }

    private function newCode(): string
    {
        do { $code = 'SV-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); } while (Ticket::where('code', $code)->exists()); return $code;
    }

    private function serializeTicket(Ticket $ticket): array
    {
        $user = request()->user(); $internal = Access::canSeeInternal($user, $ticket);
        $messages = $ticket->relationLoaded('messages') ? $ticket->messages : $ticket->messages()->with('author')->get();
        return ['id' => $ticket->id, 'code' => $ticket->code, 'type' => $ticket->type?->value, 'title' => $ticket->title, 'description' => $ticket->description, 'status' => $ticket->status?->value, 'version' => $ticket->version, 'submitted_at' => $ticket->submitted_at, 'due_at' => $ticket->due_at, 'resolved_at' => $ticket->resolved_at, 'closed_at' => $ticket->closed_at, 'student' => $ticket->relationLoaded('student') ? ['id' => $ticket->student?->id, 'full_name' => $ticket->student?->displayName()] : null, 'category' => $ticket->relationLoaded('category') ? $ticket->category?->only(['id', 'code', 'name']) : null, 'assigned_department' => $ticket->relationLoaded('assignedDepartment') ? $ticket->assignedDepartment?->only(['id', 'code', 'name']) : null, 'assigned_staff' => $ticket->relationLoaded('assignedStaff') ? ['id' => $ticket->assignedStaff?->id, 'full_name' => $ticket->assignedStaff?->displayName()] : null, 'messages' => $messages->filter(fn ($message) => $internal || $message->visibility !== MessageVisibility::STAFF_ONLY)->values()->map(fn ($m) => ['id' => $m->id, 'kind' => $m->kind?->value, 'visibility' => $m->visibility?->value, 'body' => $m->body, 'author' => $m->author?->displayName(), 'created_at' => $m->created_at])->all(), 'history' => $internal ? $ticket->statusHistory->map(fn ($h) => ['from' => $h->from_status?->value, 'to' => $h->to_status?->value, 'reason' => $h->reason, 'changed_at' => $h->created_at])->all() : []];
    }
}

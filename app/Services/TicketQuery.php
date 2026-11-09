<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class TicketQuery
{
    public const RELATIONS = ['student', 'category', 'assignedDepartment', 'assignedStaff', 'rating'];

    public function build(Request $request, User $user): Builder
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['DRAFT', 'SUBMITTED', 'ASSIGNED', 'IN_PROGRESS', 'WAITING_STUDENT', 'RESOLVED', 'CLOSED', 'REJECTED', 'CANCELLED'])],
            'type' => ['nullable', Rule::in(['FEEDBACK', 'PROPOSAL'])], 'category_id' => ['nullable', 'integer'], 'department_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'sort' => ['nullable', Rule::in(['created_at', 'submitted_at', 'updated_at', 'due_at', 'status', 'code'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'per_page' => ['nullable', 'integer', 'between:1,50'], 'page' => ['nullable', 'integer', 'min:1'],
            'view' => ['nullable', Rule::in(['mine', 'queue', 'assigned'])],
        ]);
        $q = Access::scope(Ticket::query(), $user);
        if (($data['view'] ?? null) === 'mine') {
            $q->where('student_id', $user->id);
        }
        if (($data['view'] ?? null) === 'queue') {
            $q->where('status', '!=', 'DRAFT');
        }
        if (($data['view'] ?? null) === 'assigned') {
            $q->where('assigned_staff_id', $user->id);
        }
        foreach (['status', 'type', 'category_id'] as $key) {
            if (! empty($data[$key])) {
                $q->where($key, $data[$key]);
            }
        }
        if (! empty($data['department_id'])) {
            $q->where('assigned_department_id', $data['department_id']);
        }
        if (! empty($data['search'])) {
            $q->where(fn ($s) => $s->where('title', 'like', '%'.$data['search'].'%')->orWhere('code', 'like', '%'.$data['search'].'%'));
        }
        if (! empty($data['from'])) {
            $q->where('submitted_at', '>=', Carbon::parse($data['from'], 'Asia/Ho_Chi_Minh')->startOfDay()->utc());
        }
        if (! empty($data['to'])) {
            $q->where('submitted_at', '<', Carbon::parse($data['to'], 'Asia/Ho_Chi_Minh')->addDay()->startOfDay()->utc());
        }

        return $q;
    }
}

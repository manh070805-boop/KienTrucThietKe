<?php

namespace App\Http\Resources;

use App\Domain\Tickets\Workflow;
use App\Enums\MessageVisibility;
use App\Policies\TicketPolicy;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $t = $this->resource;
        $internal = Access::canSeeInternal($request->user(), $t);
        $policy = app(TicketPolicy::class);
        $actions = [];
        $status = $t->status->value;
        foreach (Workflow::TRANSITIONS as $action => [$from,$to]) {
            if ($status === $from && $policy->act($request->user(), $t, $action)) {
                $actions[] = $action;
            }
        }
        if ($status === 'DRAFT' && $policy->act($request->user(), $t, 'update')) {
            $actions[] = 'update';
        }
        if (in_array($status, ['ASSIGNED', 'IN_PROGRESS'], true) && $policy->act($request->user(), $t, 'transfer')) {
            $actions[] = 'transfer';
        }
        if (! in_array($status, ['DRAFT', 'CLOSED', 'REJECTED', 'CANCELLED'], true)) {
            if ($policy->act($request->user(), $t, 'messages')) {
                $actions[] = 'messages';
            }
            if ($internal) {
                $actions[] = 'internal-note';
            }
            if ($policy->act($request->user(), $t, 'classify')) {
                $actions[] = 'classify';
            }
        }
        if ($status === 'CLOSED' && ! $t->rating && $policy->act($request->user(), $t, 'rate')) {
            $actions[] = 'rate';
        }
        $result = [
            'id' => $t->id, 'code' => $t->code, 'type' => $t->type->value, 'status' => $status, 'title' => $t->title, 'description' => $t->description,
            'version' => $t->version, 'category_id' => $t->category_id, 'student_id' => $t->student_id,
            'created_at' => $t->created_at, 'updated_at' => $t->updated_at, 'submitted_at' => $t->submitted_at, 'due_at' => $t->due_at,
            'resolved_at' => $t->resolved_at, 'closed_at' => $t->closed_at, 'is_overdue' => $t->isOverdue(), 'actions' => $actions, 'can_internal' => $internal,
            'student' => ['id' => $t->student->id, 'full_name' => $t->student->displayName(), 'student_code' => $t->student->student_code],
            'category' => $t->category?->only(['id', 'code', 'name']),
            'assigned_department' => $t->assignedDepartment?->only(['id', 'code', 'name']),
            'assigned_staff' => $t->assignedStaff ? ['id' => $t->assignedStaff->id, 'full_name' => $t->assignedStaff->displayName()] : null,
            'rating' => $t->rating?->only(['score', 'comment', 'created_at']),
        ];
        if ($t->relationLoaded('messages')) {
            $result['messages'] = $t->messages->filter(fn ($m) => $internal || $m->visibility !== MessageVisibility::STAFF_ONLY)->values()->map(fn ($m) => [
                'id' => $m->id, 'kind' => $m->kind->value, 'visibility' => $m->visibility->value, 'body' => $m->body, 'author' => $m->author->displayName(),
                'created_at' => $m->created_at, 'attachments' => $m->attachments->map(fn ($a) => $this->attachment($a)),
                'can_attach' => (int) $m->author_id === (int) $request->user()->id && ! in_array($status, ['CLOSED', 'REJECTED', 'CANCELLED'], true),
            ]);
            $result['attachments'] = $t->attachments->whereNull('message_id')->values()->map(fn ($a) => $this->attachment($a));
            $result['history'] = $t->statusHistory->map(fn ($h) => [
                'from' => $h->from_status?->value, 'to' => $h->to_status->value, 'changed_at' => $h->created_at,
                'reason' => $internal || $h->to_status->value === 'REJECTED' || (int) $h->changed_by === (int) $t->student_id ? $h->reason : null,
            ]);
            $result['assignments'] = $internal ? $t->assignments->map(fn ($a) => [
                'department' => $a->toDepartment->name, 'staff' => $a->toStaff->displayName(), 'reason' => $a->reason, 'created_at' => $a->created_at,
            ]) : [];
        }

        return $result;
    }

    private function attachment($a): array
    {
        return ['id' => $a->id, 'original_name' => $a->original_name, 'mime_type' => $a->mime_type, 'size_bytes' => $a->size_bytes, 'url' => '/api/attachments/'.$a->id.'/download'];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Services\TicketService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FeedbackTestCase;

final class WorkflowTest extends FeedbackTestCase
{
    public function test_all_append_only_timestamps_use_application_clock_and_mysql_utc(): void
    {
        $clock = Carbon::parse('2026-10-01 03:00:00', 'UTC');
        $this->travelTo($clock);
        try {
            $t = $this->advance('RESOLVED');
            $this->assertSame('+00:00', DB::selectOne('SELECT @@session.time_zone AS zone')->zone);
            foreach ($t->statusHistory as $h) {
                $this->assertSame($clock->timestamp, $h->created_at->timestamp);
            }
            foreach ($t->messages as $m) {
                $this->assertSame($clock->timestamp, $m->created_at->timestamp);
            }
            $epoch = DB::table('ticket_messages')->where('ticket_id', $t->id)->selectRaw('UNIX_TIMESTAMP(created_at) AS epoch')->first()->epoch;
            $this->assertSame($clock->timestamp, (int) $epoch);
        } finally {
            $this->travelBack();
        }
    }

    public function test_full_workflow_reopen_sla_and_rating(): void
    {
        $t = $this->advance('WAITING_STUDENT');
        $due = $t->due_at->toIso8601String();
        $this->postAction($this->student, $t, 'messages', ['body' => 'Đã bổ sung.'])->assertOk()->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->postAction($this->staff, $t, 'resolve', ['body' => 'Kết quả lần 1'])->assertOk()->assertJsonPath('data.status', 'RESOLVED');
        $this->postAction($this->student, $t, 'reopen', ['reason' => 'Cần kiểm tra thêm.'])->assertOk()->assertJsonPath('data.status', 'IN_PROGRESS')->assertJsonPath('data.resolved_at', null);
        $this->assertSame($due, $t->fresh()->due_at->toIso8601String());
        $this->postAction($this->staff, $t, 'resolve', ['body' => 'Kết quả lần 2'])->assertOk();
        $this->postAction($this->student, $t, 'close')->assertOk()->assertJsonPath('data.status', 'CLOSED');
        $this->postAction($this->other, $t, 'rate', ['score' => 5])->assertForbidden();
        $this->postAction($this->student, $t, 'rate', ['score' => 6])->assertUnprocessable();
        $this->postAction($this->student, $t, 'rate', ['score' => 4, 'comment' => 'Đã ổn.'])->assertOk()->assertJsonPath('data.rating.score', 4);
        $this->postAction($this->student, $t, 'rate', ['score' => 5])->assertConflict();
        $this->postAction($this->student, $t, 'reopen', ['reason' => 'Không mở CLOSED'])->assertConflict();
        $this->assertSame(2, $t->messages()->where('kind', 'RESOLUTION')->count());
    }

    public function test_submit_reads_saved_draft_and_retries_without_duplicates(): void
    {
        $t = $this->draft();
        $v = $t->version;
        $this->actingAs($this->student)->postJson('/api/tickets/'.$t->id.'/submit', ['version' => $v, 'title' => 'Ghi đè'])->assertUnprocessable();
        $first = $this->actingAs($this->student)->postJson('/api/tickets/'.$t->id.'/submit', ['version' => $v])->assertOk();
        $code = $first->json('data.code');
        $history = $t->statusHistory()->count();
        $notes = Notification::count();
        $this->actingAs($this->student)->postJson('/api/tickets/'.$t->id.'/submit', ['version' => $v])->assertOk()->assertJsonPath('data.code', $code);
        $this->assertSame($history, $t->statusHistory()->count());
        $this->assertSame($notes, Notification::count());
        $this->assertSame('Kiểm tra cổng học tập', $t->fresh()->title);
        $this->actingAs($this->student)->putJson('/api/tickets/'.$t->id, ['version' => $t->fresh()->version, 'title' => 'Sửa nội dung gốc'])->assertConflict();
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id])->assertOk();
        $this->actingAs($this->student)->postJson('/api/tickets/'.$t->id.'/submit', ['version' => $v])->assertOk()->assertJsonPath('data.status', 'ASSIGNED');
        $this->actingAs($this->student)->postJson('/api/tickets/'.$t->id.'/submit', ['version' => 99])->assertConflict();
    }

    public function test_draft_creation_is_idempotent_and_has_initial_history(): void
    {
        $uuid = (string) Str::uuid();
        $a = $this->actingAs($this->student)->postJson('/api/tickets', ['client_request_id' => $uuid, 'type' => 'FEEDBACK'])->assertCreated();
        $b = $this->postJson('/api/tickets', ['client_request_id' => $uuid, 'type' => 'PROPOSAL'])->assertOk();
        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, Ticket::where('student_id', $this->student->id)->count());
        $this->assertSame(1, TicketStatusHistory::where('ticket_id', $a->json('data.id'))->count());
    }

    public function test_blank_or_inactive_category_cannot_be_submitted(): void
    {
        $t = app(TicketService::class)->create($this->student, ['client_request_id' => (string) Str::uuid(), 'type' => 'FEEDBACK']);
        $this->postAction($this->student, $t, 'submit')->assertUnprocessable()->assertJsonValidationErrors(['title', 'description', 'category_id']);
        $t = $this->draft();
        $this->category->update(['is_active' => false]);
        $this->postAction($this->student, $t, 'submit')->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_version_is_required_and_stale_version_returns_409(): void
    {
        $t = $this->advance('ASSIGNED');
        $this->actingAs($this->staff)->postJson('/api/tickets/'.$t->id.'/start', [])->assertUnprocessable();
        $this->actingAs($this->staff)->postJson('/api/tickets/'.$t->id.'/start', ['version' => $t->version - 1])->assertConflict();
        $this->assertSame('ASSIGNED', $t->fresh()->status->value);
    }

    public function test_student_cannot_start_resolve_or_request_information(): void
    {
        $t = $this->advance('ASSIGNED');
        $this->postAction($this->student, $t, 'start')->assertForbidden();
        $this->postAction($this->coordinator, $t, 'start')->assertForbidden();
        $this->act($this->staff, $t, 'start');
        foreach (['resolve', 'request-information', 'internal-note'] as $action) {
            $this->postAction($this->student, $t, $action, ['body' => 'Giả cán bộ'])->assertForbidden();
        }
        $this->postAction($this->student, $t, 'messages', ['body' => 'Giả loại', 'kind' => 'RESOLUTION', 'visibility' => 'STAFF_ONLY'])->assertUnprocessable();
    }

    public function test_cancel_reject_and_invalid_transitions(): void
    {
        $t = $this->advance('SUBMITTED');
        $this->postAction($this->coordinator, $t, 'reject')->assertUnprocessable();
        $this->postAction($this->coordinator, $t, 'reject', ['reason' => 'Ngoài phạm vi'])->assertOk()->assertJsonPath('data.status', 'REJECTED');
        $this->postAction($this->student, $t, 'cancel')->assertConflict();
        $t = $this->advance('SUBMITTED');
        $this->postAction($this->student, $t, 'cancel')->assertOk()->assertJsonPath('data.status', 'CANCELLED');
        $t = $this->advance('ASSIGNED');
        $this->postAction($this->student, $t, 'cancel')->assertConflict();
        $this->postAction($this->student, $t, 'close')->assertConflict();
        $this->postAction($this->staff, $t, 'resolve', ['body' => 'Sai thứ tự'])->assertConflict();
        $this->actingAs($this->staff)->postJson('/api/tickets/'.$t->id.'/status', ['status' => 'CLOSED'])->assertNotFound();
    }

    public function test_message_and_status_rollback_together_when_history_fails(): void
    {
        $t = $this->advance('WAITING_STUDENT');
        $version = $t->version;
        $count = $t->messages()->count();
        TicketStatusHistory::creating(function ($h) {
            if ($h->to_status->value === 'IN_PROGRESS') {
                throw new \RuntimeException('Injected failure');
            }
        });
        try {
            app(TicketService::class)->execute($this->student, $t, 'messages', ['version' => $version, 'body' => 'Không được lưu dở']);
            $this->fail('Cần phát sinh lỗi');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected failure', $e->getMessage());
        } finally {
            TicketStatusHistory::flushEventListeners();
        }
        $this->assertSame($count, $t->messages()->count());
        $this->assertSame($version, $t->fresh()->version);
        $this->assertSame('WAITING_STUDENT', $t->fresh()->status->value);
    }

    public function test_classification_keeps_sla_and_records_audit(): void
    {
        $t = $this->advance('IN_PROGRESS');
        $due = $t->due_at->toIso8601String();
        $cat = Category::create(['code' => 'NEW_CAT', 'name' => 'Danh mục mới', 'default_department_id' => $this->department->id, 'resolution_days' => 30, 'is_active' => true]);
        $this->postAction($this->coordinator, $t, 'classify', ['category_id' => $cat->id])->assertOk();
        $this->assertSame($due, $t->fresh()->due_at->toIso8601String());
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.classify', 'subject_id' => $t->id]);
    }

    public function test_rating_before_closed_and_repeated_start_are_rejected(): void
    {
        $t = $this->advance('IN_PROGRESS');
        $this->postAction($this->student,$t,'rate',['score' => 5])->assertConflict();
        $this->postAction($this->staff,$t,'start')->assertConflict();
        $this->postAction($this->staff,$t,'request-information',[])->assertUnprocessable();
        $this->postAction($this->staff,$t,'resolve',[])->assertUnprocessable();
    }
}

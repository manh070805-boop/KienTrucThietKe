<?php

namespace Tests\Feature;

use App\Models\DepartmentMember;
use App\Models\Notification;
use App\Models\Role;
use Illuminate\Support\Str;
use Tests\FeedbackTestCase;

final class AuthorizationTest extends FeedbackTestCase
{
    public function test_admin_and_no_role_have_empty_scope_and_no_content_privilege(): void
    {
        $t = $this->advance();
        foreach ([$this->admin, $this->user(null)] as $u) {
            $this->actingAs($u)->getJson('/api/tickets')->assertOk()->assertJsonPath('meta.total', 0);
            $this->getJson('/api/tickets/'.$t->id)->assertForbidden();
            $this->getJson('/api/reports')->assertForbidden();
            $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.total_submitted', 0);
            $this->postJson('/api/tickets', ['client_request_id' => (string) Str::uuid(), 'type' => 'FEEDBACK'])->assertForbidden();
        }
        $this->admin->roles()->attach(Role::where('code', 'COORDINATOR')->first()->id);
        $this->actingAs($this->admin->fresh())->getJson('/api/tickets/'.$t->id)->assertOk();
    }

    public function test_ownership_and_coordinator_draft_isolation(): void
    {
        $draft = $this->draft();
        foreach ([$this->other, $this->coordinator, $this->staff] as $u) {
            $this->actingAs($u)->getJson('/api/tickets/'.$draft->id)->assertForbidden();
        }
        $this->actingAs($this->other)->putJson('/api/tickets/'.$draft->id, ['version' => $draft->version, 'title' => 'Giả chủ'])->assertForbidden();
        $this->getJson('/api/tickets')->assertOk()->assertJsonPath('meta.total', 0);
        $this->actingAs($this->coordinator)->getJson('/api/tickets?category_id='.$this->category->id)->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_transfer_removes_old_staff_file_notification_and_reporting_access(): void
    {
        $t = $this->advance('ASSIGNED');
        $notification = Notification::where('user_id', $this->staff->id)->where('ticket_id', $t->id)->firstOrFail();
        $this->assertGreaterThan(0, $this->staff->notifications()->count());
        $before = $t->statusHistory()->count();
        $due = $t->due_at->toIso8601String();
        $this->postAction($this->coordinator, $t, 'transfer', ['department_id' => $this->secondDepartment->id, 'staff_id' => $this->secondStaff->id])->assertUnprocessable();
        $this->postAction($this->coordinator, $t, 'transfer', ['department_id' => $this->secondDepartment->id, 'staff_id' => $this->secondStaff->id, 'reason' => 'Chuyển đúng chuyên môn'])->assertOk()->assertJsonPath('data.status', 'ASSIGNED');
        $this->assertSame($before + 1, $t->statusHistory()->count());
        $this->assertSame(2, $t->assignments()->count());
        $this->assertSame($due, $t->fresh()->due_at->toIso8601String());
        $this->actingAs($this->staff)->getJson('/api/tickets/'.$t->id)->assertForbidden();
        $this->getJson('/api/tickets')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/reports')->assertOk()->assertJsonPath('data.total_submitted', 0);
        $this->getJson('/api/notifications')->assertOk()->assertJsonPath('meta.total', 0);
        $this->postJson('/api/notifications/'.$notification->id.'/read')->assertForbidden();
        $this->postAction($this->staff, $t, 'start')->assertForbidden();
        $this->postAction($this->secondStaff, $t, 'start')->assertOk();
    }

    public function test_assignment_requires_active_staff_department_membership_and_account(): void
    {
        $t = $this->advance('SUBMITTED');
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->secondDepartment->id, 'staff_id' => $this->staff->id])->assertUnprocessable();
        $this->staff->update(['is_active' => false]);
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id])->assertUnprocessable();
        $this->staff->update(['is_active' => true]);
        DepartmentMember::where('user_id', $this->staff->id)->update(['is_active' => false]);
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id])->assertUnprocessable();
        DepartmentMember::where('user_id', $this->staff->id)->update(['is_active' => true]);
        $this->department->update(['is_active' => false]);
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id])->assertUnprocessable();
        $this->department->update(['is_active' => true]);
        $this->staff->roles()->detach();
        $this->postAction($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id])->assertUnprocessable();
    }

    public function test_internal_notes_are_removed_but_student_progress_remains(): void
    {
        $t = $this->advance();
        $this->postAction($this->staff, $t, 'internal-note', ['body' => 'SECRET_INTERNAL_123'])->assertOk();
        $this->actingAs($this->student)->getJson('/api/tickets/'.$t->id)->assertOk()->assertDontSee('SECRET_INTERNAL_123')->assertJsonCount(4, 'data.history')->assertJsonCount(0, 'data.messages');
        $this->actingAs($this->coordinator)->getJson('/api/tickets/'.$t->id)->assertOk()->assertSee('SECRET_INTERNAL_123');
        $this->actingAs($this->student)->getJson('/api/notifications')->assertDontSee('SECRET_INTERNAL_123');
    }

    public function test_multi_role_scope_is_union_and_inactive_member_loses_access(): void
    {
        $own = $this->draft();
        $assigned = $this->advance();
        $assigned->forceFill(['student_id' => $this->other->id])->save();
        $this->student->roles()->attach(Role::where('code', 'STAFF')->first()->id);
        DepartmentMember::create(['department_id' => $this->department->id, 'user_id' => $this->student->id, 'is_active' => true]);
        $this->postAction($this->coordinator, $assigned, 'transfer', ['department_id' => $this->department->id, 'staff_id' => $this->student->id, 'reason' => 'Tài khoản nhiều vai trò'])->assertOk();
        $this->actingAs($this->student->fresh())->getJson('/api/tickets')->assertOk()->assertJsonPath('meta.total', 2);
        DepartmentMember::where('user_id', $this->student->id)->update(['is_active' => false]);
        $this->postAction($this->student->fresh(), $assigned, 'start')->assertForbidden();
    }

    public function test_list_filters_sort_and_real_pagination(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->draft();
        }
        $this->advance('SUBMITTED');
        $this->actingAs($this->student)->getJson('/api/tickets?per_page=2&page=2&sort=created_at&direction=asc')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 4);
        $this->getJson('/api/tickets?status=SUBMITTED&type=FEEDBACK&search=Kiểm')->assertOk()->assertJsonPath('meta.total',1);
        $this->getJson('/api/tickets?type=PROPOSAL')->assertOk()->assertJsonPath('meta.total',0);
        $this->getJson('/api/tickets?sort=password')->assertUnprocessable();
        $this->getJson('/api/tickets?per_page=1000')->assertUnprocessable();
    }
}

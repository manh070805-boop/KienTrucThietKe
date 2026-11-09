<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\FeedbackTestCase;

final class AuthAdminReportTest extends FeedbackTestCase
{
    public function test_json_auth_login_logout_and_locked_account(): void
    {
        $this->get('/api/tickets')->assertUnauthorized()->assertHeader('content-type', 'application/json')->assertJsonPath('message', 'Vui lòng đăng nhập.');
        $this->getJson('/api/tickets')->assertUnauthorized()->assertHeader('content-type', 'application/json');
        $this->postJson('/api/login', ['email' => $this->student->email, 'password' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => $this->student->email, 'password' => 'Password123!'])->assertOk()->assertJsonPath('data.roles.0', 'STUDENT')->assertDontSee('remember_token');
        $this->getJson('/api/me')->assertOk();
        $this->postJson('/api/logout')->assertOk();
        $this->student->update(['is_active' => false]);
        $this->postJson('/api/login', ['email' => $this->student->email, 'password' => 'Password123!'])->assertUnauthorized();
        $this->actingAs($this->student)->getJson('/api/dashboard')->assertForbidden();
        $this->postJson('/api/logout')->assertOk();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'limited@test.local', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/login', ['email' => 'limited@test.local', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_admin_crud_is_restricted_validated_and_hashed(): void
    {
        foreach (['users', 'roles', 'departments', 'members', 'categories'] as $entity) {
            $this->actingAs($this->student)->getJson('/api/admin/'.$entity)->assertForbidden();
        }
        $r = $this->actingAs($this->admin)->postJson('/api/admin/users', ['full_name' => 'Cán bộ mới', 'email' => 'new@test.local', 'password' => 'StrongPassword123!', 'roles' => ['STAFF'], 'is_active' => true])->assertCreated();
        $id = $r->json('data.id');
        $this->assertTrue(Hash::check('StrongPassword123!', User::find($id)->password));
        $this->postJson('/api/admin/members', ['department_id' => $this->department->id, 'user_id' => $id, 'is_active' => true])->assertCreated();
        $this->postJson('/api/admin/members', ['department_id' => $this->department->id, 'user_id' => $id, 'is_active' => true])->assertUnprocessable();
        $this->postJson('/api/admin/departments', ['code' => 'NEW_DEP', 'name' => 'Đơn vị mới', 'is_active' => true])->assertCreated();
        $this->postJson('/api/admin/categories', ['code' => 'NEW_C', 'name' => 'Danh mục mới', 'default_department_id' => $this->department->id, 'resolution_days' => 0, 'is_active' => true])->assertUnprocessable();
        $this->putJson('/api/admin/users/'.$this->admin->id, ['full_name' => 'Admin', 'email' => $this->admin->email, 'roles' => ['STUDENT'], 'is_active' => true])->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.users.create', 'subject_id' => $id]);
        $this->getJson('/api/admin/users')->assertDontSee('password');
    }

    public function test_known_report_aggregates_filters_and_csv(): void
    {
        $this->draft();
        $open = $this->advance('SUBMITTED');
        $open->forceFill(['due_at' => now()->subMinute()])->save();
        $closed = $this->advance('CLOSED');
        $this->act($this->student, $closed, 'rate', ['score' => 5]);
        $resolved = $this->advance('RESOLVED');
        $resolved->forceFill(['due_at' => now()->subMinute()])->save();
        $this->actingAs($this->coordinator)->getJson('/api/reports?category_id='.$this->category->id)->assertOk()->assertJsonPath('data.total_submitted', 3)->assertJsonPath('data.overdue_open', 1)->assertJsonPath('data.rating.average', 5)->assertJsonPath('data.rating.total', 1);
        $this->getJson('/api/reports?category_id='.$this->category->id.'&status=SUBMITTED')->assertOk()->assertJsonPath('data.total_submitted', 1)->assertJsonPath('data.rating.total', 0)->assertJsonPath('data.rating.average', null);
        $this->getJson('/api/reports?category_id='.$this->category->id.'&type=PROPOSAL')->assertJsonPath('data.total_submitted', 0);
        $this->getJson('/api/reports?from=2099-01-01&to=2099-02-01')->assertJsonPath('data.total_submitted', 0);
        $this->get('/api/reports/export')->assertOk()->assertDownload('bao-cao-ho-so.csv');
        $empty = $this->actingAs($this->other)->getJson('/api/reports')->assertOk();
        $json = json_decode($empty->getContent());
        foreach (['by_status', 'by_type', 'by_category', 'by_department'] as $key) {
            $this->assertIsObject($json->data->{$key});
        }
    }
}

<?php

namespace Tests;

use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class FeedbackTestCase extends TestCase
{
    private static bool $migrated = false;

    protected User $student;

    protected User $other;

    protected User $coordinator;

    protected User $staff;

    protected User $secondStaff;

    protected User $admin;

    protected Department $department;

    protected Department $secondDepartment;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'student_feedback_test') {
            throw new \RuntimeException('Chỉ cho phép kiểm thử trên MySQL student_feedback_test riêng.');
        }
        if (! self::$migrated) {
            Artisan::call('migrate', ['--force' => true]);
            self::$migrated = true;
        }
        DB::beginTransaction();
        foreach (['STUDENT', 'COORDINATOR', 'STAFF', 'ADMIN'] as $code) {
            Role::firstOrCreate(['code' => $code], ['name' => $code]);
        }
        $this->student = $this->user('STUDENT');
        $this->other = $this->user('STUDENT');
        $this->coordinator = $this->user('COORDINATOR');
        $this->staff = $this->user('STAFF');
        $this->secondStaff = $this->user('STAFF');
        $this->admin = $this->user('ADMIN');
        $this->department = Department::create(['code' => 'TEST_A', 'name' => 'Đơn vị A', 'is_active' => true]);
        $this->secondDepartment = Department::create(['code' => 'TEST_B', 'name' => 'Đơn vị B', 'is_active' => true]);
        foreach ([[$this->department, $this->staff], [$this->secondDepartment, $this->secondStaff]] as [$d,$u]) {
            DepartmentMember::create(['department_id' => $d->id, 'user_id' => $u->id, 'is_active' => true]);
        }
        $this->category = Category::create(['code' => 'TEST_CAT', 'name' => 'Danh mục thử nghiệm', 'default_department_id' => $this->department->id, 'resolution_days' => 7, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        } parent::tearDown();
    }

    protected function user(?string $role): User
    {
        $key = bin2hex(random_bytes(6));
        $u = User::create(['name' => 'Người dùng thử', 'full_name' => 'Người dùng thử '.$key, 'email' => $key.'@test.local', 'password' => Hash::make('Password123!'), 'is_active' => true]);
        if ($role) {
            $u->roles()->attach(Role::where('code', $role)->firstOrFail()->id);
        }

        return $u;
    }

    protected function draft(): Ticket
    {
        $s = app(TicketService::class);
        $t = $s->create($this->student, ['client_request_id' => (string) Str::uuid(), 'type' => 'FEEDBACK']);

        return $s->execute($this->student, $t, 'update', ['version' => $t->version, 'title' => 'Kiểm tra cổng học tập', 'description' => 'Mô tả đầy đủ cho việc kiểm tra.', 'category_id' => $this->category->id]);
    }

    protected function advance(string $status = 'IN_PROGRESS'): Ticket
    {
        $t = $this->draft();
        if ($status === 'DRAFT') {
            return $t;
        }
        $t = $this->act($this->student, $t, 'submit');
        if ($status === 'SUBMITTED') {
            return $t;
        }
        if ($status === 'CANCELLED') {
            return $this->act($this->student, $t, 'cancel');
        }
        if ($status === 'REJECTED') {
            return $this->act($this->coordinator, $t, 'reject', ['reason' => 'Không thuộc phạm vi.']);
        }
        $t = $this->act($this->coordinator, $t, 'assign', ['department_id' => $this->department->id, 'staff_id' => $this->staff->id]);
        if ($status === 'ASSIGNED') {
            return $t;
        }
        $t = $this->act($this->staff, $t, 'start');
        if ($status === 'IN_PROGRESS') {
            return $t;
        }
        if ($status === 'WAITING_STUDENT') {
            return $this->act($this->staff, $t, 'request-information', ['body' => 'Cần thông tin bổ sung.']);
        }
        $t = $this->act($this->staff, $t, 'resolve', ['body' => 'Đã xử lý và khắc phục.']);
        if ($status === 'RESOLVED') {
            return $t;
        }

        return $this->act($this->student, $t, 'close');
    }

    protected function act(User $u, Ticket $t, string $action, array $data = []): Ticket
    {
        return app(TicketService::class)->execute($u, $t, $action, ['version' => $t->fresh()->version] + $data);
    }

    protected function postAction(User $u, Ticket $t, string $action, array $data = [])
    {
        return $this->actingAs($u)->postJson('/api/tickets/'.$t->id.'/'.$action,['version' => $t->fresh()->version] + $data);
    }
}

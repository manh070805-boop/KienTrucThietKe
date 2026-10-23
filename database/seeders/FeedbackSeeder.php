<?php

namespace Database\Seeders;

use App\Enums\MessageKind;
use App\Enums\MessageVisibility;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketMessage;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [];
        foreach ([['STUDENT', 'Sinh viên'], ['COORDINATOR', 'Điều phối'], ['STAFF', 'Cán bộ xử lý'], ['ADMIN', 'Quản trị viên']] as [$code, $name]) $roles[$code] = Role::firstOrCreate(['code' => $code], ['name' => $name]);
        $departments = collect([['PCTSV', 'Phòng Công tác sinh viên'], ['PDT', 'Phòng Đào tạo'], ['CNTT', 'Trung tâm Công nghệ thông tin']])->mapWithKeys(fn ($d) => [$d[0] => Department::firstOrCreate(['code' => $d[0]], ['name' => $d[1], 'is_active' => true])]);
        $categories = collect([['HOC_BONG', 'Học bổng và hỗ trợ', 'PCTSV'], ['KY_TUC_XA', 'Ký túc xá', 'PCTSV'], ['LICH_HOC', 'Lịch học và lớp học', 'PDT'], ['DIEM_THI', 'Điểm và kết quả học tập', 'PDT'], ['TAI_KHOAN', 'Tài khoản sinh viên', 'CNTT'], ['CO_SO_VAT_CHAT', 'Cơ sở vật chất', 'PCTSV']])->map(fn ($c) => Category::firstOrCreate(['code' => $c[0]], ['name' => $c[1], 'default_department_id' => $departments[$c[2]]->id, 'resolution_days' => 7, 'is_active' => true]));
        $password = Hash::make('Password123!');
        $studentUsers = collect();
        for ($i = 1; $i <= 20; $i++) $studentUsers->push($this->user("student{$i}@demo.local", "Sinh viên {$i}", "SV".str_pad((string) $i, 4, '0', STR_PAD_LEFT), $password, $roles['STUDENT']));
        $coordinator = $this->user('coordinator@demo.local', 'Điều phối viên', null, $password, $roles['COORDINATOR']);
        $admin = $this->user('admin@demo.local', 'Quản trị viên', null, $password, $roles['ADMIN']);
        $staff = collect();
        foreach ([['staff1@demo.local', 'Cán bộ PCTSV 1', 'PCTSV'], ['staff2@demo.local', 'Cán bộ PCTSV 2', 'PCTSV'], ['staff3@demo.local', 'Cán bộ Đào tạo', 'PDT'], ['staff4@demo.local', 'Cán bộ CNTT', 'CNTT']] as [$email, $name, $dept]) {
            $u = $this->user($email, $name, null, $password, $roles['STAFF']); $staff->push($u); DepartmentMember::firstOrCreate(['department_id' => $departments[$dept]->id, 'user_id' => $u->id], ['is_active' => true]);
        }
        $statuses = [TicketStatus::DRAFT, TicketStatus::SUBMITTED, TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_STUDENT, TicketStatus::RESOLVED, TicketStatus::CLOSED, TicketStatus::REJECTED, TicketStatus::CANCELLED];
        for ($i = 1; $i <= 100; $i++) {
            $status = $statuses[($i - 1) % count($statuses)]; $student = $studentUsers[($i - 1) % $studentUsers->count()]; $category = $categories[($i - 1) % $categories->count()]; $assigned = $status->value === 'DRAFT' || in_array($status, [TicketStatus::SUBMITTED, TicketStatus::REJECTED, TicketStatus::CANCELLED], true) ? null : $staff[($i - 1) % $staff->count()]; $dept = $assigned ? DepartmentMember::where('user_id', $assigned->id)->first() : null; $submitted = $status === TicketStatus::DRAFT ? null : now()->subDays($i % 18); $due = $submitted?->copy()->subDays($i % 4 === 0 ? 2 : -7);
            $ticket = Ticket::forceCreate(['code' => $status === TicketStatus::DRAFT ? null : 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'student_id' => $student->id, 'client_request_id' => (string) Str::uuid(), 'type' => $i % 2 ? 'FEEDBACK' : 'PROPOSAL', 'title' => $status === TicketStatus::DRAFT ? null : 'Hồ sơ demo số '.$i, 'description' => $status === TicketStatus::DRAFT ? null : 'Nội dung minh họa phục vụ trình diễn đồ án.', 'category_id' => $status === TicketStatus::DRAFT ? null : $category->id, 'status' => $status->value, 'assigned_department_id' => $dept?->department_id, 'assigned_staff_id' => $assigned?->id, 'submitted_at' => $submitted, 'due_at' => $due, 'resolved_at' => in_array($status, [TicketStatus::RESOLVED, TicketStatus::CLOSED], true) ? now()->subDays(1) : null, 'closed_at' => $status === TicketStatus::CLOSED ? now()->subHours(4) : null, 'version' => 3]);
            TicketStatusHistory::create(['ticket_id' => $ticket->id, 'from_status' => null, 'to_status' => 'DRAFT', 'changed_by' => $student->id]); if ($status !== TicketStatus::DRAFT) TicketStatusHistory::create(['ticket_id' => $ticket->id, 'from_status' => 'DRAFT', 'to_status' => $status === TicketStatus::SUBMITTED || in_array($status, [TicketStatus::REJECTED, TicketStatus::CANCELLED], true) ? $status->value : 'SUBMITTED', 'changed_by' => $student->id]); if ($assigned) { TicketAssignment::create(['ticket_id' => $ticket->id, 'to_department_id' => $dept->department_id, 'to_staff_id' => $assigned->id, 'assigned_by' => $coordinator->id, 'reason' => 'Phân công dữ liệu demo']); if (!in_array($status, [TicketStatus::ASSIGNED, TicketStatus::SUBMITTED, TicketStatus::REJECTED, TicketStatus::CANCELLED], true)) TicketStatusHistory::create(['ticket_id' => $ticket->id, 'from_status' => 'ASSIGNED', 'to_status' => $status->value, 'changed_by' => $assigned->id]); }
            if ($status !== TicketStatus::DRAFT) TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => $student->id, 'kind' => MessageKind::COMMENT, 'visibility' => MessageVisibility::STUDENT_VISIBLE, 'body' => 'Trao đổi công khai của sinh viên trong hồ sơ demo.']); if (in_array($status, [TicketStatus::WAITING_STUDENT, TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => $assigned->id, 'kind' => $status === TicketStatus::WAITING_STUDENT ? MessageKind::INFO_REQUEST : MessageKind::RESOLUTION, 'visibility' => MessageVisibility::STUDENT_VISIBLE, 'body' => 'Cập nhật xử lý minh họa từ cán bộ.']); if ($assigned && $i % 3 === 0) TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => $assigned->id, 'kind' => MessageKind::COMMENT, 'visibility' => MessageVisibility::STAFF_ONLY, 'body' => 'Ghi chú nội bộ minh họa, không hiển thị cho sinh viên.']);
        }
    }

    private function user(string $email, string $name, ?string $studentCode, string $password, Role $role): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'full_name' => $name, 'student_code' => $studentCode, 'password' => $password, 'is_active' => true]); $user->roles()->syncWithoutDetaching([$role->id]); return $user;
    }
}

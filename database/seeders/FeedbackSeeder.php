<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

final class FeedbackSeeder extends Seeder
{
    public const PREFIX = 'de000000-0000-4000-8000-';

    public function run(): void
    {
        if (! app()->environment('local', 'testing') && ! (app()->environment('e2e') && DB::connection()->getDatabaseName() === 'student_feedback_test')) {
            throw new \RuntimeException('Seed demo chỉ dùng môi trường local/testing hoặc E2E trên database test riêng.');
        }
        $anchor = Carbon::now('UTC')->startOfSecond();
        $existing = Ticket::where('client_request_id', 'like', self::PREFIX.'%')->count();
        if ($existing === 100) {
            $this->command?->info('Dataset demo đã tồn tại: không sửa dữ liệu hoặc nhân đôi. Chạy demo:verify để đối chiếu.');

            return;
        }
        if ($existing > 0) {
            throw new \RuntimeException('Dataset demo không đầy đủ. Không ghi đè; kiểm tra bản sao lưu trước.');
        }
        try {
            DB::transaction(function () use ($anchor) {
                $roles = [];
                foreach (['STUDENT' => 'Sinh viên', 'COORDINATOR' => 'Điều phối', 'STAFF' => 'Cán bộ xử lý', 'ADMIN' => 'Quản trị viên'] as $code => $name) {
                    $roles[$code] = Role::firstOrCreate(['code' => $code], ['name' => $name]);
                }
                $departments = [];
                foreach (['PCTSV' => 'Phòng Công tác sinh viên', 'PDT' => 'Phòng Đào tạo', 'CNTT' => 'Trung tâm Công nghệ thông tin'] as $code => $name) {
                    $departments[] = Department::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
                }
                $categories = [];
                foreach ([['HOC_BONG', 'Học bổng và hỗ trợ', 0], ['KY_TUC_XA', 'Ký túc xá', 0], ['LICH_HOC', 'Lịch học và lớp học', 1], ['DIEM_THI', 'Điểm và kết quả học tập', 1], ['TAI_KHOAN', 'Tài khoản sinh viên', 2], ['CO_SO_VAT_CHAT', 'Cơ sở vật chất', 0]] as [$code,$name,$dep]) {
                    $categories[] = Category::firstOrCreate(['code' => $code], ['name' => $name, 'default_department_id' => $departments[$dep]->id, 'resolution_days' => 7, 'is_active' => true]);
                }
                $students = [];
                for ($i = 1; $i <= 20; $i++) {
                    $students[] = $this->user("student$i@demo.local", "Sinh viên Nguyễn An $i", 'SV'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), $roles['STUDENT']);
                }
                $coordinator = $this->user('coordinator@demo.local', 'Điều phối Trần Minh', null, $roles['COORDINATOR']);
                $this->user('admin@demo.local', 'Quản trị Lê Hà', null, $roles['ADMIN']);
                $staff = [];
                foreach ([0, 0, 1, 2] as $i => $dep) {
                    $staff[] = $u = $this->user('staff'.($i + 1).'@demo.local', ['Cán bộ Võ Lan', 'Cán bộ Đỗ Nam', 'Cán bộ Phạm Bình', 'Cán bộ Hoàng Anh'][$i], null, $roles['STAFF']);
                    DepartmentMember::firstOrCreate(['department_id' => $departments[$dep]->id, 'user_id' => $u->id], ['is_active' => true]);
                }
                $service = app(TicketService::class);
                $scores = [1, 2, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5];
                $topics = [
                    ['Đối soát học bổng khuyến khích', 'Sinh viên đã nộp hồ sơ học bổng nhưng bảng đối soát chưa có tên. Đề nghị kiểm tra minh chứng và thông báo kết quả.'],
                    ['Sửa vòi nước phòng ký túc xá', 'Vòi nước tại khu vệ sinh bị rò liên tục, ảnh hưởng việc sinh hoạt. Đề nghị đơn vị bố trí kiểm tra và sửa chữa.'],
                    ['Điều chỉnh lịch thực hành', 'Lịch thực hành bị trùng với tiết học bắt buộc. Đề nghị xem xét bố trí ca học phù hợp.'],
                    ['Rà soát điểm quá trình', 'Điểm bài thực hành trên hệ thống khác với bảng điểm giảng viên công bố. Đề nghị kiểm tra lại bảng nhập điểm.'],
                    ['Khôi phục truy cập cổng học tập', 'Tài khoản báo lỗi khi đăng nhập dù mật khẩu vừa được cập nhật. Đề nghị kiểm tra trạng thái tài khoản.'],
                    ['Bảo trì máy chiếu giảng đường', 'Máy chiếu hiển thị mờ và chớp sáng trong buổi học. Đề nghị kiểm tra thiết bị trước buổi học kế tiếp.'],
                ];
                for ($i = 1; $i <= 100; $i++) {
                    $status = $this->status($i);
                    $student = $students[($i - 1) % 20];
                    $category = $categories[($i - 1) % 6];
                    $old = in_array($status, ['RESOLVED', 'CLOSED', 'REJECTED', 'CANCELLED'], true) || ($i >= 9 && $i <= 18);
                    $time = $anchor->copy()->subDays($old ? 15 : 2)->addMinutes($i);
                    Carbon::setTestNow($time->copy()->subHour());
                    $t = $service->create($student, ['client_request_id' => self::PREFIX.str_pad((string) $i, 12, '0', STR_PAD_LEFT), 'type' => $i % 2 ? 'FEEDBACK' : 'PROPOSAL']);
                    [$title,$description] = $topics[($i - 1) % 6];
                    $t = $service->execute($student, $t, 'update', ['version' => $t->version, 'title' => $title.' – đợt '.(int) ceil($i / 6).' / vị trí '.$i,
                        'description' => $description.' Tình huống giả lập số '.$i.', tại khu '.chr(65 + ($i % 5)).', buổi '.($i % 2 ? 'sáng' : 'chiều').'.', 'category_id' => $category->id]);
                    if ($status === 'DRAFT') {
                        continue;
                    }
                    Carbon::setTestNow($time);
                    $t = $service->execute($student, $t, 'submit', ['version' => $t->version]);
                    $t->forceFill(['code' => 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)])->save();
                    Notification::where('ticket_id', $t->id)->where('event_type', 'submit')->update(['title' => 'Hồ sơ '.$t->code.' có cập nhật']);
                    if ($status === 'SUBMITTED') {
                        continue;
                    }
                    $time->addMinutes(10);
                    Carbon::setTestNow($time);
                    if (in_array($status, ['REJECTED', 'CANCELLED'], true)) {
                        $service->execute($status === 'REJECTED' ? $coordinator : $student, $t, strtolower($status === 'REJECTED' ? 'reject' : 'cancel'), ['version' => $t->version, 'reason' => 'Đã kiểm tra: '.($status === 'REJECTED' ? 'nội dung nằm ngoài phạm vi hỗ trợ của nhà trường.' : 'sinh viên đã tự khắc phục và xin rút hồ sơ.')]);

                        continue;
                    }
                    $member = DepartmentMember::where('department_id', $category->default_department_id)->orderBy('id')->firstOrFail();
                    $handler = User::findOrFail($member->user_id);
                    $t = $service->execute($coordinator, $t, 'assign', ['version' => $t->version, 'department_id' => $member->department_id, 'staff_id' => $handler->id, 'reason' => 'Tiếp nhận đúng chuyên môn.']);
                    if (in_array($i, [21, 22, 33], true)) {
                        $newDep = $departments[(array_search($category->default_department_id, array_map(fn ($d) => $d->id, $departments)) + 1) % 3];
                        $member = DepartmentMember::where('department_id', $newDep->id)->firstOrFail();
                        $handler = User::findOrFail($member->user_id);
                        Carbon::setTestNow($time->addMinutes(10));
                        $t = $service->execute($coordinator, $t, 'transfer', ['version' => $t->version, 'department_id' => $member->department_id, 'staff_id' => $handler->id, 'reason' => 'Đã xác minh, chuyển đơn vị phối hợp theo chuyên môn.']);
                    }
                    if ($status === 'ASSIGNED') {
                        continue;
                    }
                    Carbon::setTestNow($time->addMinutes(10));
                    $t = $service->execute($handler, $t, 'start', ['version' => $t->version]);
                    $t = $service->execute($handler, $t, 'internal-note', ['version' => $t->version, 'body' => 'Ghi chú nội bộ: đang đối soát với đầu mối đơn vị. Không chia sẻ tài liệu làm việc nội bộ.']);
                    if ($i >= 33 && $i <= 38) {
                        Carbon::setTestNow($time->addMinutes(10));
                        $t = $service->execute($handler, $t, 'resolve', ['version' => $t->version, 'body' => 'Đã kiểm tra bước đầu và cập nhật hệ thống. Vui lòng xác nhận kết quả.']);
                        Carbon::setTestNow($time->addMinutes(10));
                        $t = $service->execute($student, $t, 'reopen', ['version' => $t->version, 'reason' => 'Vẫn còn lỗi tại bước xác nhận; đề nghị kiểm tra lần hai.']);
                    }
                    if ($status === 'IN_PROGRESS') {
                        continue;
                    }
                    Carbon::setTestNow($time->addMinutes(10));
                    $t = $service->execute($handler, $t, 'request-information', ['version' => $t->version, 'body' => 'Vui lòng bổ sung thời điểm xảy ra và minh chứng để đơn vị đối chiếu.']);
                    if ($status === 'WAITING_STUDENT') {
                        continue;
                    }
                    Carbon::setTestNow($time->addMinutes(10));
                    $t = $service->execute($student, $t, 'messages', ['version' => $t->version, 'body' => 'Em bổ sung: sự việc xảy ra lúc 08:30 trong buổi học, đã kiểm tra lại và đính kèm minh chứng.']);
                    Carbon::setTestNow($time->addMinutes(10));
                    $t = $service->execute($handler, $t, 'resolve', ['version' => $t->version, 'body' => 'Đơn vị đã kiểm tra và hoàn tất khắc phục theo nội dung đề nghị. Sinh viên có thể kiểm tra và xác nhận đóng hồ sơ.']);
                    if ($status === 'RESOLVED') {
                        continue;
                    }
                    Carbon::setTestNow($time->addMinutes(10));
                    $t = $service->execute($student, $t, 'close', ['version' => $t->version]);
                    if ($i <= 82) {
                        $service->execute($student, $t, 'rate', ['version' => $t->version, 'score' => $scores[$i - 71], 'comment' => 'Đánh giá giả lập sau khi kiểm tra kết quả.']);
                    }
                }
                Carbon::setTestNow($anchor);
                $this->files($students, $service);
                foreach (Notification::orderBy('id')->get() as $n) {
                    if ($n->id % 3 === 0) {
                        $n->forceFill(['read_at' => $n->created_at->copy()->addMinutes(5)])->save();
                    }
                }
                AuditLog::create(['actor_id' => null, 'action' => 'demo.seed', 'subject_type' => 'Dataset', 'metadata' => ['anchor_utc' => $anchor->toIso8601String(), 'timeline_version' => 2, 'tickets' => 100, 'submitted' => 92, 'overdue_open' => 10, 'ratings' => 12, 'average' => 3.75]]);
            });
        } finally {
            Carbon::setTestNow();
        }
        $this->command?->info('Đã tạo 100 hồ sơ demo theo workflow. Chạy php artisan demo:verify.');
    }

    private function user(string $email, string $name, ?string $code, Role $role): User
    {
        if (User::where('email', $email)->exists()) {
            throw new \RuntimeException("Tài khoản $email đã tồn tại ngoài dataset mới; dừng để bảo toàn dữ liệu.");
        }
        $u = User::create(['name' => $name, 'full_name' => $name, 'email' => $email, 'student_code' => $code, 'is_active' => true, 'password' => Hash::make('Password123!')]);
        $u->roles()->attach($role->id);

        return $u;
    }

    private function status(int $i): string
    {
        foreach ([8 => 'DRAFT', 20 => 'SUBMITTED', 32 => 'ASSIGNED', 46 => 'IN_PROGRESS', 58 => 'WAITING_STUDENT', 70 => 'RESOLVED', 86 => 'CLOSED', 93 => 'REJECTED', 100 => 'CANCELLED'] as $last => $s) {
            if ($i <= $last) {
                return $s;
            }
        }
        throw new \LogicException;
    }

    private function files(array $students, TicketService $service): void
    {
        $pdf = "%PDF-1.4\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $stream = 'BT /F1 16 Tf 25 100 Td (Student feedback - demo evidence) Tj ET';
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream";
        $offsets = [0];
        foreach ($objects as $idx => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($idx + 1)." 0 obj\n$obj\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
        $img = imagecreatetruecolor(300, 120);
        $bg = imagecolorallocate($img, 4, 102, 69);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 5, 20, 50, 'MINH CHUNG DEMO', imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        ob_start();
        imagejpeg($img);
        $jpg = ob_get_clean();
        imagedestroy($img);
        foreach ([[1, null, $pdf, 'application/pdf', 'minh-chung-nhap.pdf'], [47, null, $png, 'image/png', 'loi-man-hinh.png'], [33, 'internal', $jpg, 'image/jpeg', 'doi-soat-noi-bo.jpg']] as [$i,$internal,$bytes,$mime,$name]) {
            $t = Ticket::where('client_request_id', self::PREFIX.str_pad((string) $i, 12, '0', STR_PAD_LEFT))->firstOrFail();
            $message = $internal ? $t->messages()->where('visibility', 'STAFF_ONLY')->firstOrFail() : null;
            $key = 'tickets/'.$t->id.'/demo-'.$name;
            Storage::disk('local')->put($key,$bytes);
            Attachment::create(['ticket_id' => $t->id, 'message_id' => $message?->id, 'uploaded_by' => $message?->author_id ?? $t->student_id,
                'original_name' => $name, 'storage_key' => $key, 'mime_type' => $mime, 'size_bytes' => strlen($bytes)])->forceFill(['created_at' => $message?->created_at ?? $t->created_at])->save();
        }
    }
}

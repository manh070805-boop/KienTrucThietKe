<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = DB::connection()->getDatabaseName();
if (DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('Cần MySQL.');
}
$business = ['users', 'roles', 'user_roles', 'departments', 'department_members', 'categories', 'tickets', 'ticket_messages', 'attachments', 'ticket_assignments', 'ticket_status_history', 'notifications', 'ticket_ratings', 'audit_logs'];
$technical = ['migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
$meanings = ['id' => 'Khóa định danh', 'name' => 'Tên tương thích Laravel/tên dữ liệu nền', 'full_name' => 'Họ tên hiển thị', 'email' => 'Email đăng nhập duy nhất', 'password' => 'Mật khẩu đã băm', 'student_code' => 'Mã sinh viên duy nhất khi có', 'is_active' => 'Cho phép sử dụng khi còn hoạt động', 'code' => 'Mã duy nhất; hồ sơ chỉ có mã sau khi gửi', 'client_request_id' => 'UUID ổn định cho retry tạo nháp', 'student_id' => 'Chủ hồ sơ', 'category_id' => 'Danh mục hiện tại', 'type' => 'FEEDBACK hoặc PROPOSAL', 'status' => 'Trạng thái theo Workflow', 'title' => 'Tiêu đề/nội dung thông báo', 'description' => 'Nội dung gốc bất biến sau gửi', 'assigned_department_id' => 'Đơn vị hiện phụ trách', 'assigned_staff_id' => 'Cán bộ hiện phụ trách', 'version' => 'Phiên bản tăng mỗi cập nhật', 'submitted_version' => 'Phiên bản trước lần gửi thành công; nhận diện retry', 'submitted_at' => 'Thời điểm gửi UTC', 'due_at' => 'Hạn cố định UTC từ SLA danh mục', 'resolved_at' => 'Kết quả hiện tại; xóa khi xử lý lại', 'closed_at' => 'Thời điểm sinh viên xác nhận đóng', 'created_at' => 'Thời điểm tạo UTC', 'updated_at' => 'Thời điểm cập nhật UTC', 'ticket_id' => 'Hồ sơ liên quan', 'message_id' => 'Tin nhắn cùng hồ sơ; NULL là minh chứng gốc', 'kind' => 'COMMENT/INFO_REQUEST/INFO_RESPONSE/RESOLUTION', 'visibility' => 'STUDENT_VISIBLE hoặc STAFF_ONLY', 'body' => 'Nội dung trao đổi/thông báo', 'author_id' => 'Người tạo tin nhắn', 'uploaded_by' => 'Người tải tệp', 'original_name' => 'Tên hiển thị của tệp', 'storage_key' => 'Khóa riêng tư do server sinh', 'mime_type' => 'Loại nội dung đã kiểm tra', 'size_bytes' => 'Dung lượng 1–5242880 byte', 'score' => 'Điểm 1–5', 'comment' => 'Nhận xét không bắt buộc', 'metadata' => 'Ngữ cảnh JSON; không lưu bí mật/nội dung nhạy cảm', 'actor_id' => 'Người thao tác', 'action' => 'Tên use case được ghi nhận', 'subject_type' => 'Loại đối tượng audit', 'subject_id' => 'ID đối tượng audit', 'department_id' => 'Đơn vị thành viên', 'user_id' => 'Tài khoản', 'role_id' => 'Vai trò', 'default_department_id' => 'Đơn vị mặc định của danh mục', 'resolution_days' => 'SLA số ngày lịch, lớn hơn 0', 'from_department_id' => 'Đơn vị trước chuyển', 'from_staff_id' => 'Cán bộ trước chuyển', 'to_department_id' => 'Đơn vị mới', 'to_staff_id' => 'Cán bộ mới', 'assigned_by' => 'Điều phối phân công', 'reason' => 'Lý do nghiệp vụ; lý do phân công là nội bộ', 'from_status' => 'Trạng thái trước; NULL ở khởi tạo', 'to_status' => 'Trạng thái sau', 'changed_by' => 'Người chuyển trạng thái', 'event_type' => 'Loại sự kiện', 'read_at' => 'Thời điểm đọc; NULL là chưa đọc', 'remember_token' => 'Trường kỹ thuật Laravel, không trả API', 'email_verified_at' => 'Trường kỹ thuật Laravel; MVP không dùng xác minh email'];
$out = "# Từ điển dữ liệu và ràng buộc MySQL\n\nSinh tự động bằng php scripts/export-design.php từ migrations đã chạy trên $database (MySQL ".DB::selectOne('SELECT VERSION() v')->v."). Không phải thiết kế SQL độc lập.\n\n14 bảng nghiệp vụ: ".implode(', ', $business).".\n\nBảng kỹ thuật Laravel: ".implode(', ', $technical).". users.name, email_verified_at, remember_token giữ tương thích Laravel.\n";
foreach ($business as $table) {
    $out .= "\n## $table\n\n| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |\n|---|---|---|---|---|---|\n";
    foreach (DB::select('SELECT COLUMN_NAME n,COLUMN_TYPE t,IS_NULLABLE z,COLUMN_DEFAULT d,COLUMN_KEY k FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', [$database, $table]) as $c) {
        $out .= "| $c->n | $c->t | $c->z | ".($c->d ?? 'NULL')." | $c->k | ".($meanings[$c->n] ?? 'Trường kỹ thuật')." |\n";
    }
    $create = DB::select("SHOW CREATE TABLE $table")[0]->{'Create Table'};
    $out .= "\n<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>\n\n~~~sql\n$create;\n~~~\n</details>\n";
}
file_put_contents(base_path('docs/DATA_DICTIONARY.md'), $out);
$student = DB::table('users')->where('email', 'student1@demo.local')->value('id');
$staff = DB::table('users')->where('email', 'staff1@demo.local')->value('id');
$plans = [
    'Hồ sơ của sinh viên' => ['SELECT id,title,status FROM tickets WHERE student_id=? ORDER BY created_at DESC,id DESC LIMIT 15', [$student]],
    'Hàng đợi' => ["SELECT id,title,status FROM tickets WHERE status='SUBMITTED' ORDER BY submitted_at DESC,id DESC LIMIT 15", []],
    'Công việc được giao' => ["SELECT id,title,status FROM tickets WHERE assigned_staff_id=? AND status='IN_PROGRESS' ORDER BY updated_at DESC,id DESC LIMIT 15", [$staff]],
    'Đang mở quá hạn' => ["SELECT id FROM tickets WHERE status IN ('SUBMITTED','ASSIGNED','IN_PROGRESS','WAITING_STUDENT') AND due_at < UTC_TIMESTAMP()", []],
    'Dòng thời gian' => ['SELECT id,body FROM ticket_messages WHERE ticket_id=33 ORDER BY created_at,id', []],
    'Thông báo chưa đọc' => ['SELECT id FROM notifications WHERE user_id=? AND read_at IS NULL ORDER BY created_at DESC LIMIT 20', [$student]],
];
$out = "# EXPLAIN truy vấn thực tế\n\nDataset demo 100 hồ sơ; đây là kiểm tra kế hoạch truy vấn, không phải đo tải 10.000 hồ sơ/50 người.\n";
foreach ($plans as $name => [$sql,$params]) {
    $plan = DB::select('EXPLAIN FORMAT=JSON '.$sql, $params)[0]->EXPLAIN;
    $out .= "\n## $name\n\n~~~sql\n$sql\n~~~\n\nTham số: ".json_encode($params)."\n\n~~~json\n$plan\n~~~\n";
}
$out .= "\nIndex được giữ theo truy vấn và FK. Với bảng nhỏ, optimizer có thể chọn quét bảng; chưa chứng minh hiệu năng ở tải lớn. Xem key/possible_keys và rows_examined_per_scan trong kết quả trên.\n";
file_put_contents(base_path('docs/QUERY_PLANS.md'), $out);
echo "Đã xuất DATA_DICTIONARY.md và QUERY_PLANS.md.\n";

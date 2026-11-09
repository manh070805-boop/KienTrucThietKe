# Dữ liệu demo có thể đối chiếu

Toàn bộ dữ liệu giả lập. Nguồn: FeedbackSeeder, Workflow/TicketService và lệnh php artisan demo:verify. Kết quả thực tế ở [demo-statistics.json](demo-statistics.json); kiểm chứng cài sạch riêng ở [clean-install-check.json](clean-install-check.json).

Dataset chính student_feedback có mốc seed 2026-10-28T08:25:57Z, tức 15:25:57 ngày 28/10/2026 tại Việt Nam. “Đúng 10 quá hạn” tính tại mốc này; số quá hạn sẽ thay đổi theo thời gian thật hoặc thao tác demo sau bàn giao. Không ép tổng 92 lên database có hồ sơ khác.

## Số lượng

| Bảng | Dataset hiện tại |
|---|---:|
| users | 26 |
| roles | 4 |
| user_roles | 26 |
| departments | 3 |
| department_members | 4 |
| categories | 6 |
| tickets | 100 |
| ticket_messages | 162 |
| attachments | 3 |
| ticket_assignments | 69 |
| ticket_status_history | 453 |
| notifications | 415 |
| ticket_ratings | 12 |
| audit_logs | 621 |

Seed mới hoàn toàn tạo 620 audit; database chính có thêm 1 audit sửa timeline UTC đã được sao lưu và đối chiếu. Không tính các bảng kỹ thuật Laravel vào 14 bảng nghiệp vụ. Bảng sessions/cache có thể thay đổi khi đăng nhập.

| Trạng thái | Số lượng | Dải thứ tự seed |
|---|---:|---|
| DRAFT | 8 | 1–8 |
| SUBMITTED | 12 | 9–20 |
| ASSIGNED | 12 | 21–32 |
| IN_PROGRESS | 14 | 33–46 |
| WAITING_STUDENT | 12 | 47–58 |
| RESOLVED | 12 | 59–70 |
| CLOSED | 16 | 71–86 |
| REJECTED | 7 | 87–93 |
| CANCELLED | 7 | 94–100 |

Hồ sơ đã gửi: 92; FEEDBACK 46, PROPOSAL 46. Hồ sơ đang mở quá hạn tại seed: đúng DEMO-0009…DEMO-0018. Hạn = submitted_at + 7 ngày, không trừ/đổi hạn tùy trạng thái.

Đánh giá: 1 điểm ×1; 2 điểm ×1; 3 điểm ×2; 4 điểm ×4; 5 điểm ×4. Tổng điểm 45/12 = 3,75. CLOSED DEMO-0083…0086 chưa được đánh giá. DEMO-0033…0038 có lịch sử RESOLVED → IN_PROGRESS; DEMO-0021, 0022, 0033 đã chuyển đơn vị/cán bộ. Chuyển từ ASSIGNED vẫn có history và assignment.

## Tài khoản

Mật khẩu chung Password123! chỉ dùng demo, được hash khi lưu.

| Email | Vai trò/phạm vi |
|---|---|
| student1@demo.local … student20@demo.local | STUDENT, mỗi người 5 hồ sơ |
| coordinator@demo.local | COORDINATOR, 92 hồ sơ đã gửi, không 8 nháp |
| staff1@demo.local | STAFF, Phòng CTSV; 33 hồ sơ hiện được giao |
| staff2@demo.local | STAFF, Phòng CTSV; thành viên hợp lệ, chưa được giao hồ sơ trong seed |
| staff3@demo.local | STAFF, Phòng Đào tạo; 19 hồ sơ hiện được giao |
| staff4@demo.local | STAFF, Trung tâm CNTT; 14 hồ sơ hiện được giao |
| admin@demo.local | ADMIN thuần, quản trị 26 tài khoản/3 đơn vị/4 thành viên/6 danh mục; không đọc nội dung |

staff1 và staff2 đều thuộc CTSV; staff3 thuộc Đào tạo; staff4 thuộc CNTT. Sáu danh mục: Học bổng và hỗ trợ; Ký túc xá; Lịch học và lớp học; Điểm và kết quả học tập; Tài khoản sinh viên; Cơ sở vật chất.

student1 thấy nháp thứ tự 1 và DEMO-0021, 0041, 0061, 0081. Dashboard riêng có 4 hồ sơ đã gửi; không dùng số toàn trường 92/3,75 cho dashboard sinh viên. ADMIN+COORDINATOR, tài khoản bị khóa/sai vai trò/thành viên ngừng được tạo trong fixture test, không trộn vào 26 tài khoản demo.

## Hồ sơ trình diễn

| Tình huống | Mã/định danh | Người dùng |
|---|---|---|
| Nháp có PDF mở được | Thứ tự 1, client_request_id de000000-0000-4000-8000-000000000001 | student1 |
| Chờ tiếp nhận quá hạn | DEMO-0009 | coordinator |
| Đã chuyển phân công khi cùng ASSIGNED | DEMO-0021, 0022 | coordinator; người nhận hiện tại xem được |
| Xử lý lại, ghi chú và JPEG nội bộ | DEMO-0033 | staff4/coordinator; student13 không thấy JPEG/nội bộ |
| Chờ sinh viên, PNG minh chứng gốc | DEMO-0047 | student7 và cán bộ hiện tại |
| Đã trả lời, chờ xác nhận | DEMO-0061 | student1 |
| Đóng, đã đánh giá | DEMO-0081 | student1 |
| Đóng, chưa đánh giá | DEMO-0083 | student3 |
| Bị từ chối, có lý do | DEMO-0087 | student7 |
| Đã rút | DEMO-0094 | student14 |

ID vật lý có thể khác trên database khác. Tìm theo code/UUID; không viết client cố định id=1 hay 33. Seed dùng UUID namespace ổn định, code DEMO-xxxx cho hồ sơ đã gửi. Hồ sơ tạo qua UI dùng mã SV-ngày-hex.

## Cách kiểm chứng

~~~powershell
php artisan demo:verify
php artisan db:seed --force
php artisan demo:verify
~~~

Lệnh kiểm tra số lượng/trạng thái, phân bố điểm, số lần xử lý lại/chuyển, chuỗi history từ DRAFT tới trạng thái cuối, thứ tự timestamp, SLA, file tồn tại/size/MIME và file gốc trước khi gửi. Seed chạy lại không nhân hoặc đưa hồ sơ đã được demo về trạng thái ban đầu.

Tệp PDF có trang/chữ thật; PNG/JPG là ảnh minh chứng giả lập. Tất cả ở disk local ngoài public và tải qua Policy. Không có URL public cố định hoặc storage_key trong JSON.

Nếu muốn dataset sạch cho lần trình diễn mới, tạo database mới rồi migrate/seed; không reset database có dữ liệu. Chế độ tạo 10.000 hồ sơ và benchmark chưa được chạy.

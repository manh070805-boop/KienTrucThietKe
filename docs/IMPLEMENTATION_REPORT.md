# Báo cáo triển khai

Ngày 28/10/2026. Phạm vi: ứng dụng đồ án local trong D:/laragon/www/KienTrucThietKe. Không commit/push/merge/deploy công khai.

## Kế thừa và bảo toàn

Giữ Laravel 12, React/TSX, Vite, enums, models và thiết kế 14 bảng nghiệp vụ. Đã đọc cả attachment gốc và đặc tả tiếp tục. Ban đầu Git có run.bat, dev.bat, docs chưa theo dõi; giữ tài liệu lịch sử và cập nhật scripts theo nhu cầu chạy thật. Không thay framework, không migrate:fresh, không xóa database cũ. SQLite gốc/bản sao và .env trước chuyển đổi giữ trong project/backups.

## Thay đổi chính

| Phần | Đã sửa/thêm | Mã nguồn tiêu biểu |
|---|---|---|
| MySQL/schema | Instance 8.4.3 riêng 3307, schema/check bổ sung, sửa index phân công không UNIQUE, submitted_version | migrations 000002/000003/000004/000005 (backfill full_name rồi NOT NULL), config/database.php |
| Xác thực | Session/CSRF cùng origin, regenerate sau login/logout, throttle, chặn tài khoản khóa, JSON lỗi | IdentityService, LoginRequest, ActiveAccount, bootstrap/app.php, config/cors.php |
| Quyền | Mặc định từ chối, hợp quyền đa vai trò; ADMIN không đọc mặc nhiên; staff hiện tại và thành viên hoạt động | Access, TicketPolicy |
| Workflow | Tách Service/Request/Resource; lock + version trong transaction; submit từ nháp đã lưu; retry đúng lần gửi | TicketService, TicketRequest, Workflow, TicketResource |
| Nhất quán | Message + status + history/audit/notification cùng commit; chuyển ASSIGNED vẫn lưu assignment/history; SLA giữ nguyên | TicketService, ConcurrencyTest |
| Tệp | PDF/JPG/PNG private, 3 tệp/5 MB, kiểm nội dung/MIME, download Policy, bù trừ metadata lỗi, cleanup orphan | AttachmentService/Controller, files:cleanup |
| Thông báo | Generic body, không ghi chú nội bộ; list/read kiểm quyền hiện tại sau chuyển | NotificationController, TicketService |
| Quản trị | Thêm/sửa/ngừng tài khoản/đơn vị/thành viên/danh mục, gán role; bảo vệ tham chiếu/hồ sơ đang xử lý | AdminRequest, AdminService, AdminController |
| Báo cáo | Lọc ngày Việt Nam/loại/trạng thái/danh mục/đơn vị, nhóm/overdue/AVG, CSV an toàn công thức | TicketQuery, ReportingService, ReportController |
| Frontend | Tách pages/components/hooks/types/client/router; form/dialog thật; pagination/filter API; trạng thái lỗi/loading; mobile | resources/js, resources/css/app.css |
| Lỗi hết phiên | Giữ component/form khi cùng tài khoản đăng nhập lại, đóng/mở lại dialog có chủ đích | app.tsx, api.ts, useDialog |
| Seed | 100 hồ sơ đi qua use case, UUID ổn định, lịch sử đầy đủ, tệp thật, 12 điểm/3,75; verify toàn vẹn | FeedbackSeeder, demo:verify |
| UTC | Session MySQL +00:00; model nhật ký lấy clock ứng dụng, seed/test giả lập thời gian đúng | RecordsCreatedAt, config/database.php |
| Tài liệu | Setup, walkthrough, quyền/SRS, ERD/dictionary, diagrams, ADR/OpenAPI, test/truy vết, demo/FAQ | docs |

Nguồn phong cách Lattice: xanh #046645, nền trắng, card/form/spacing thống nhất; không sao chép template trả phí hay phân phối phông Matter. PHP được định dạng bằng Pint có sẵn, frontend bằng Prettier để dễ đọc.

## Lỗi tìm được nhờ kiểm tra và cách xử lý

- Lượt test đầu kỳ vọng nháp mới HTTP 200, trong khi Resource đúng là 201; sửa test để phân biệt tạo mới/retry.
- Browser phát hiện form select cần nhãn ổn định và UI cho hành động tiếp khi version chưa tải xong; bổ sung aria-label và disable trong lúc reload.
- Ảnh browser cho thấy timestamp tin nhắn lệch 7 giờ. Nguyên nhân: MySQL SYSTEM +07:00 và created_at mặc định DB cho model timestamps=false. Đã đặt session UTC, thêm RecordsCreatedAt, test epoch với clock ứng dụng và sửa đúng dataset mới tạo sau backup. Không áp dụng script sửa vào dữ liệu SQLite cũ.
- Các test ban đầu giả định test database trống; sau E2E có dữ liệu, đã chuyển sang fixture ID/danh mục riêng và dọn chính xác ID, không reset.
- Hết phiên: giữ form qua màn hình đăng nhập lại cùng tài khoản; có E2E cố ý logout phiên để kiểm tra.
- Giữ seed không sửa dữ liệu người dùng; một lần căn chỉnh timeline/metadata của dataset do triển khai này tạo được ghi audit và script có guard.

## Đã chạy kiểm chứng

Chi tiết lệnh/phiên bản/kết quả cuối ở TEST_REPORT.md; dữ liệu gốc JUnit, Playwright và JSON cài sạch được dẫn tại đó. Đã chạy MySQL migrations/seed, verify/seed lặp, 32 tests (344 assertions), typecheck, build, browser workflow/các vai trò/mobile và backup/restore riêng. Kết quả browser sau thay đổi cuối được cập nhật trong TEST_REPORT, không suy từ build.

Cài sạch độc lập được thực hiện tại student_feedback_installcheck_20261028_095753: migrations/seed/verify/seed lần hai đều exit 0. Không tạo giả kết quả bằng SQLite.

## Giới hạn được công bố

- Chưa chạy Docker Compose vì không có Docker CLI; Compose mới cung cấp database, chưa container hóa toàn bộ app.
- Chưa tạo/đo tải 10.000 hồ sơ, 50 người và p95 <2 giây; EXPLAIN chỉ trên dataset nhỏ.
- Chưa nghiệm thu production HTTPS, đa instance, SSO, email, quét mã độc/PDF parser đầy đủ, hay khôi phục thảm họa diện rộng.
- Không autosave nền: rời trang/đổi tài khoản có thể mất form chưa lưu; đăng nhập lại cùng tài khoản giữ form.
- Không có cơ chế SQL ngăn DBA sửa lịch sử; bảo vệ append-only nằm ở API/model và quyền vận hành.
- Mã ngẫu nhiên có UNIQUE, chưa có xử lý retry chuyên biệt cho va chạm mã cực hiếm.
- Cấu trúc module theo trách nhiệm Service, giữ Laravel folders; chưa có package isolation tự động.

Không gọi các giới hạn trên là đã nghiệm thu hoặc “sẵn sàng production”.

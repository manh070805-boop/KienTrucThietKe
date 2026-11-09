# Báo cáo kiểm thử thực tế

Ngày chạy: 28/10/2026, múi giờ hiển thị Việt Nam. Không dùng SQLite hoặc mock để suy ra FK/CHECK/locking MySQL.

## Môi trường

PHP 8.3.33, Laravel 12.70.0, MySQL 8.4.3/InnoDB/utf8mb4; Node 24.14.1, Vite 7.3.7, React 19.3.1, TypeScript 7.0.2; Chrome qua Playwright 1.64.1. Windows Laragon, một PHP development server, browser workers=1. MySQL session +00:00.

Database demo: student_feedback. Backend/browser test: student_feedback_test riêng; tệp browser ở storage/app/e2e-private, tệp cạnh tranh ở storage/framework/testing/private. Demo/SQLite cũ không bị reset. Không chạy test backend và browser đồng thời.

## Lệnh và kết quả

| Kiểm tra | Lệnh/bằng chứng | Kết quả |
|---|---|---|
| Backend | php artisan test --compact --log-junit storage/app/phpunit-results.xml | 32 test đạt, 344 assertions, 0 lỗi/skip; JUnit là kết quả gốc |
| Kiểu TS | npm.cmd run typecheck | Đạt, tsc --noEmit |
| Bundling | npm.cmd run build | Đạt, Vite build |
| Browser | npm.cmd run test:e2e | 4 đạt, 0 lỗi/skip/flaky; lượt đủ 174,98 giây (~2,9 phút) |
| E2E chi tiết | storage/app/e2e-results.json | 4 expected, 0 unexpected, 0 flaky, 0 skipped |
| Dataset | php artisan demo:verify | 100 hồ sơ, 92 đã gửi, 10 quá hạn tại anchor, 12 điểm/AVG 3,75, integrity_errors=[] |
| Seed lặp | php artisan db:seed --force, rồi demo:verify | Không tạo thêm/ghi đè |
| Cài sạch | php scripts/verify-clean-install.php | migrate/seed/verify/seed lặp exit 0, JSON lưu tên DB riêng |
| OpenAPI | php scripts/verify-openapi.php | 46 operations khớp route Laravel thực |
| Schema/EXPLAIN | php scripts/export-design.php; mysqldump --no-data | Xuất từ MySQL đã migrate |
| Restore | mysqldump → DB tên mới; php scripts/verify-restore.php | 14 bảng nghiệp vụ khớp số lượng; hàng/timestamp trọng tâm khớp; FK bật; 3 tệp backup khớp metadata |
| URL bàn giao | node scripts/smoke-local.mjs | Đăng nhập UI đủ bốn vai trò trên 8000; tổng riêng 4/92/33/0 đúng scope |
| HMR | node scripts/smoke-hmr.mjs khi bật Vite | React render, preamble đúng, không pageerror; đã tắt Vite/xóa hot file để trở lại build |
| Bật/tắt | scripts/stop-web.ps1 rồi bật lại 8000; start.ps1 nhận server đã chạy | Đạt smoke, URL /up 200 và UI login/dashboard thật |
| Git | git diff --check | Không lỗi whitespace; không push/merge |

Kết quả gốc: [local-smoke.json](local-smoke.json), [clean-install-check.json](clean-install-check.json), [restore-check.json](restore-check.json), [demo-statistics.json](demo-statistics.json). JUnit và trace/screenshot nằm trong storage/app, không đưa backup/database lên Git.

## Những gì backend đã kiểm tra

- Login/logout (kể cả phiên tài khoản vừa khóa), sai mật khẩu, throttle; /api/* chưa đăng nhập trả JSON 401 cả khi không có Accept JSON.
- ADMIN thuần/người không vai trò không có scope đọc; ADMIN+COORDINATOR có quyền rõ; đa vai trò là hợp quyền.
- Sinh viên khác chủ, điều phối đọc nháp, cán bộ chưa được giao, thành viên/đơn vị/tài khoản ngừng, sai vai trò đều bị chặn.
- Cán bộ cũ sau chuyển mất list/detail/action/report/notification/file; chuyển ASSIGNED → ASSIGNED vẫn ghi history/assignment, giữ due_at.
- Ma trận action–state, luồng đúng/sai, không API tùy ý đặt status; sinh viên không start/resolve/request-information.
- Submit lấy nháp đã lưu, nội dung gốc bất biến, chống UUID/submit retry, kể cả xử lý đã tiến bước sau lần gửi.
- Version bắt buộc, version cũ 409; lỗi history rollback cả INFO_RESPONSE/status/version.
- Hai tiến trình MySQL cùng version: đúng một 200, một 409; hai upload tranh vị trí tệp thứ ba: đúng một 201, một 409, cuối cùng chỉ 3 tệp.
- Đóng/xử lý lại/giữ kết quả/xóa resolved_at, đánh giá đúng chủ/CLOSED/một lần/1–5.
- Tệp sai nội dung/dung lượng/số lượng, tệp nội bộ/khác chủ/message khác ticket; lỗi metadata xóa file vừa ghi.
- FK, UNIQUE, CHECK trực tiếp trên MySQL: cặp cán bộ/đơn vị, trạng thái/phân công, loại, SLA, score, file message pair, size/MIME, chống xóa tài khoản được tham chiếu.
- Báo cáo fixture có đáp án biết trước, không DRAFT, quá hạn chỉ trạng thái mở, trung bình chỉ rating, filter/CSV.
- Session MySQL UTC và epoch tin nhắn/history theo clock ứng dụng; không chỉ so chuỗi hiển thị.
- Nhóm báo cáo rỗng vẫn là JSON object theo OpenAPI/TypeScript.

## Kịch bản browser đã chạy

1. Sinh viên tạo/lưu nháp, tải PNG, gửi, reload; điều phối phân công; cán bộ bắt đầu, ghi nội bộ, yêu cầu bổ sung; sinh viên bổ sung; trả lời, yêu cầu xử lý lại, trả lời lần hai, đóng, đánh giá; report tăng đúng tổng/đóng/lượt đánh giá; tải tệp thật. Cố ý logout phiên trước bổ sung, đăng nhập lại và xác nhận textarea giữ nội dung.
2. ADMIN thuần trên 390px: menu đúng, không nút tạo hồ sơ; server từ chối đọc hồ sơ, POST thiếu CSRF trả 419; thêm tài khoản bằng form, logout.
3. UI tạo hồ sơ cho từng nhánh rút/từ chối/chuyển; nhập lý do thật, cán bộ cũ reload bị 403; thông báo mở được. Không phụ thuộc ID cố định hoặc reset dataset seed.
4. Viewport 390×844 cho cả STUDENT, COORDINATOR, STAFF, ADMIN: trang/menu đúng, dữ liệu tải xong, không tràn chiều ngang toàn trang; chụp ảnh riêng.

Ảnh ở storage/app/e2e-student-detail.png, e2e-admin-mobile.png, mobile-{student,coordinator,staff,admin}.png và dashboard-{role}.png. Đã mở xem ảnh desktop/mobile để kiểm tra layout; bảng dài cuộn trong vùng bảng.

## Lỗi đã gặp và xử lý

Lượt đầu test kỳ vọng nháp mới 200 thay vì 201; sửa kỳ vọng theo Resource. Browser phát hiện nhãn select/textarea chưa ổn định và bấm tiếp trước khi version mới về; bổ sung aria-label/disable lúc tải. Kiểm ảnh phát hiện lệch timestamp 7 giờ; sửa session UTC và clock model, có test epoch riêng. Test cài sạch/CSV vận hành sau formatter phát hiện import Kernel nằm sau chỗ dùng; chuyển imports lên đầu và chạy lại script thật.

Một lượt kịch bản gộp nhiều nhánh chạm timeout 120 giây tại bước cuối; trace cho thấy các hành động nghiệp vụ đã hoàn tất, không phải lỗi assertion. Bộ test hiện có timeout 180 giây và expect 15 giây để phù hợp môi trường Windows development server. Lượt cuối đủ 4 test đều đạt. Đây không phải bằng chứng đạt mục tiêu hiệu năng dưới tải.

## Chưa kiểm chứng

Docker Compose chưa chạy (không có Docker CLI). Chưa benchmark 10.000 hồ sơ/50 người hoặc p95 <2 giây; không suy ra hiệu năng từ HTTP 200, build, EXPLAIN hay tổng thời gian E2E. Chưa kiểm tra production HTTPS/đa instance/SSO/email/antivirus/khôi phục thảm họa diện rộng.

Các test có phạm vi nêu trên; không khẳng định bao phủ mọi trình duyệt, mọi thiết bị hay mọi tổ hợp lỗi mạng. Bản bàn giao là đồ án local đã chạy kiểm chứng, chưa được chứng nhận production.

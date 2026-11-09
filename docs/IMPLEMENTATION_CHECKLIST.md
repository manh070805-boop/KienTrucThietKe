# Checklist bàn giao ngày 28/10/2026

Đã đối chiếu attachment gốc và đặc tả tiếp tục. Giữ Laravel 12.70.0, React 19 và Vite 7; không reset dữ liệu cũ, không push/merge.

- [x] Rà soát Git/mã/dependencies/môi trường; giữ run.bat, dev.bat, docs và các tệp người dùng có sẵn.
- [x] MySQL 8.4.3 riêng ở 3307; migrations, FK/UNIQUE/CHECK, full_name backfill, schema.sql/ERD/dictionary/EXPLAIN.
- [x] Policy/scope mặc định từ chối, đa vai trò rõ ràng, ADMIN thuần không đọc hồ sơ.
- [x] Workflow/transaction/lock/version/retry, nội dung gốc/SLA, phân công/chuyển/xử lý lại/đánh giá.
- [x] Resource/FormRequest/Service; tệp private, giới hạn upload đồng thời và bù trừ lỗi filesystem.
- [x] Thông báo, quản trị, báo cáo/CSV và đầy đủ trang React có route/reload/phân trang.
- [x] Seed 100 hồ sơ qua workflow, lịch sử/tệp/đánh giá thật; verify toàn vẹn và chạy lặp.
- [x] UTC MySQL + clock ứng dụng; kiểm tra epoch, timestamp và SLA.
- [x] Backend MySQL: 32 test, 344 assertions; typecheck và build.
- [x] Browser: 4 kịch bản đạt, bốn vai trò/mobile, hết phiên giữ form; HMR smoke đạt.
- [x] Cài sạch migrations/seed/verify/seed lặp trên DB riêng; backup/restore đối chiếu hàng/timestamp/tệp.
- [x] URL 8000 kiểm tra đăng nhập thật đủ bốn vai trò; script dừng/bật thử thành công; dừng server E2E, giữ server demo.
- [x] README và bộ docs thiết kế, vận hành, test/truy vết, demo, giải thích và bảo vệ khớp mã.

Chưa nghiệm thu do ngoài bằng chứng hiện tại: Docker runtime (không có CLI), tải 10.000 hồ sơ/50 người, production HTTPS/đa máy. Cấu hình Compose đã có, không tuyên bố đã chạy. Xem TEST_REPORT.md và IMPLEMENTATION_REPORT.md.

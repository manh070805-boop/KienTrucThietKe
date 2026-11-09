# Hệ thống tiếp nhận phản ánh và kiến nghị của sinh viên

Laravel 12.70.0 + PHP 8.3.33, React 19.3.1 + TypeScript 7.0.2 + Vite 7.3.7, MySQL 8.4.3. Giao diện tiếng Việt, dữ liệu lưu qua API, đăng nhập bằng session/cookie cùng origin.

## Chạy trên máy hiện tại

~~~powershell
cd D:\laragon\www\KienTrucThietKe
.\run.bat
~~~

URL: http://127.0.0.1:8000. MySQL riêng ở 127.0.0.1:3307, database student_feedback; kiểm thử dùng student_feedback_test. Cài từ đầu theo [SETUP](docs/SETUP.md). Dừng web foreground bằng Ctrl+C. Với server nền đang bàn giao: powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/stop-web.ps1 -Port 8000. Cách dừng MySQL riêng và chạy dev ở SETUP.

Tất cả tài khoản dưới đây chỉ là demo, mật khẩu Password123! (lưu dưới dạng băm):

| Vai trò | Email |
|---|---|
| Sinh viên | student1@demo.local |
| Điều phối | coordinator@demo.local |
| Cán bộ CTSV | staff1@demo.local |
| Cán bộ Đào tạo | staff3@demo.local |
| Cán bộ CNTT | staff4@demo.local |
| ADMIN thuần | admin@demo.local |

ADMIN quản trị dữ liệu nền; chỉ được đọc hồ sơ khi có thêm vai trò nghiệp vụ phù hợp. Dataset chính có 100 hồ sơ, 92 đã gửi, 10 đang mở quá hạn tại mốc seed, 12 đánh giá trung bình 3,75/5. Chi tiết và mốc thời gian ở [DEMO_DATA](docs/DEMO_DATA.md).

## Kiểm tra

~~~powershell
php artisan test --compact --log-junit storage/app/phpunit-results.xml
npm.cmd run typecheck
npm.cmd run build
php artisan demo:verify
~~~

Browser test chạy qua server 8001 trên database test riêng; xem SETUP. Không chạy reset/migrate:fresh trên database có dữ liệu. Kết quả và giới hạn ở [TEST_REPORT](docs/TEST_REPORT.md); đây là bản đồ án chạy local, chưa được chứng nhận production hay đo tải 50 người/10.000 hồ sơ.

## Đọc để hiểu và bảo vệ đồ án

1. [PROJECT_WALKTHROUGH](docs/PROJECT_WALKTHROUGH.md): từ nút bấm đến SQL.
2. [SRS và use case](docs/SRS.md), [ma trận quyền](docs/PERMISSIONS.md).
3. [ERD](docs/ERD.md), [từ điển dữ liệu](docs/DATA_DICTIONARY.md), [sơ đồ](docs/DIAGRAMS.md).
4. [OpenAPI](docs/openapi.json), [quyết định kiến trúc](docs/adr/0001-decisions.md).
5. [Báo cáo triển khai](docs/IMPLEMENTATION_REPORT.md), [truy vết yêu cầu](docs/TRACEABILITY.md).
6. [Hướng dẫn người dùng](docs/USER_GUIDE.md), [kịch bản demo](docs/DEMO_SCRIPT.md), [câu hỏi bảo vệ](docs/FAQ_DEFENSE.md).

## Quy trình đóng góp

Giữ quy trình nhóm cũ: Issue → nhánh feat/fix/docs theo số Issue → triển khai và tự kiểm tra → review Pull Request → merge khi nhóm duyệt. Lần triển khai này không tự commit, push hoặc merge.

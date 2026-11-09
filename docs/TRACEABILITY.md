# Truy vết yêu cầu → mã/thiết kế → kiểm chứng

Nguồn: attachment gốc 61bcb1c6…/pasted-text.txt và đặc tả tiếp tục 2f04371f…/Pasted text.txt. “Gốc/tiếp §” chỉ mục trong hai yêu cầu, không phải số mục tài liệu này. Kết quả chạy cuối: TEST_REPORT.md. Các hàng “đạt” dựa trên test/lệnh thực tế, không chỉ có mã.

| ID / nguồn | Yêu cầu | Mã/thiết kế | Test/bằng chứng | Kết quả |
|---|---|---|---|---|
| ENV / gốc§1–2, tiếp§1–3 | Giữ framework, đọc hiện trạng, không reset cũ | SETUP, backups, lockfiles | migrations/clean-install-check.json, Git diff | Đạt local |
| DB01 / gốc§6, tiếp§4 | 14 bảng + bảng kỹ thuật, BIGINT/InnoDB/utf8mb4 | migrations, ERD, DATA_DICTIONARY, schema.sql | MySqlConstraintTest, cài sạch MySQL 8.4.3 | Đạt |
| DB02 | UNIQUE email/student_code/code/UUID/rating | migrations | MySqlConstraintTest, WorkflowTest retry | Đạt |
| DB03 | Cán bộ thuộc đơn vị, không UNIQUE cặp tickets | FK ghép, migration 000002 | MySqlConstraintTest, AuthorizationTest assignment/transfer | Đạt |
| DB04 | Message/file cùng hồ sơ, referenced UNIQUE rõ | attachments_message_ticket_fk | MySqlConstraintTest, AttachmentTest cross-ticket | Đạt |
| DB05 | CHECK enum/score/SLA/phân công/metadata | migration gốc + 000003 | MySqlConstraintTest trên MySQL | Đạt |
| DB06 | Index/EXPLAIN, schema từ migrations | export-design.php, QUERY_PLANS | EXPLAIN MySQL thật | Đạt dataset nhỏ |
| DB07 | Không cascade xóa nghiệp vụ, giữ lịch sử | FK restrict, API ngừng hoạt động, append-only model | MySqlConstraintTest delete user, AdminService | Đạt |
| AUTH / gốc§2, tiếp§3 | Session, CSRF, cookie HttpOnly, throttle | IdentityService, routes, middleware, CORS | AuthAdminReportTest; E2E thiếu CSRF 419, cookie HttpOnly/Lax | Đạt |
| ACL01 / gốc§4, tiếp§5 | Chính chủ, coordinator không nháp, staff hiện tại | Access + TicketPolicy + Resource | AuthorizationTest, AttachmentTest, browser chuyển | Đạt |
| ACL02 | ADMIN không mặc nhiên đọc, đa vai trò hợp quyền | Access scope 1=0 + OR | AuthorizationTest admin/no-role/multi-role | Đạt |
| ACL03 | Không lộ nội bộ ở file/message/history/notify | Resource, AttachmentService, NotificationController | AuthorizationTest internal; AttachmentTest private | Đạt |
| WF01 / gốc§5, tiếp§6 | Các cạnh trạng thái đúng/sai, không arbitrary status | Workflow + TicketService | WorkflowMatrixTest + WorkflowTest + browser | Đạt |
| WF02 | Nháp sửa được, nội dung gốc bất biến, submit không nhận lại | TicketRequest/Service, DraftEditor | WorkflowTest blank/immutable/retry | Đạt |
| WF03 | due_at chốt, classify/chuyển/chờ/reopen không gia hạn | TicketService | WorkflowTest SLA/classify; AuthorizationTest transfer | Đạt |
| WF04 | Chuyển có lý do, về ASSIGNED, history cùng trạng thái | assignment/history transaction | AuthorizationTest transfer + browser | Đạt |
| WF05 | Xử lý lại giữ kết quả cũ, xóa resolved_at; CLOSED không mở | TicketService + Resource | WorkflowTest full flow; browser reopen | Đạt |
| TX01 / gốc§7, tiếp§6 | Transaction/lock/version, lỗi 409 | TicketService/AttachmentService | stale version + rollback tests | Đạt |
| TX02 | UUID retry/submit lost-response không nhân đôi | submitted_version, owner lock | WorkflowTest submit/draft retry | Đạt |
| TX03 | Cạnh tranh không ghi đè, upload không vượt ba | ticket row lock | ConcurrencyTest: hai tiến trình 200/409; upload 201/409 | Đạt MySQL |
| FILE / gốc§8, tiếp§7 | PDF/JPG/PNG, 5 MB, 3, private, rollback FS | AttachmentService, cleanup | 5 AttachmentTest, ConcurrencyTest, E2E download | Đạt phạm vi kiểm nội dung cơ bản |
| MSG / gốc§5, tiếp§6 | COMMENT/INFO_REQUEST/INFO_RESPONSE/RESOLUTION; không giả loại | TicketRequest + Service | WorkflowTest + Resource tests + E2E | Đạt |
| RATE / gốc§9, tiếp§7 | Chính chủ CLOSED đánh giá một lần | Policy/Service/UNIQUE | WorkflowTest và browser đóng/đánh giá | Đạt |
| NOTICE / gốc§9, tiếp§7 | Read/unread, mất quyền sau chuyển | NotificationController query | AuthorizationTest transfer; browser notifications | Đạt |
| ADMIN / gốc§9, tiếp§8 | Quản trị users/roles/departments/members/categories | AdminRequest/Service/Page | AuthAdminReportTest; browser thêm user; đọc danh sách các tab | Đạt API, UI chính |
| REPORT / gốc§11, tiếp§7 | Tổng/nhóm/overdue/AVG/lọc/CSV, cùng scope | TicketQuery/ReportingService/Reports | known-report fixture; browser metrics thay đổi | Đạt |
| UI / gốc§10, tiếp§8 | Routes/reload, form/error/loading, pagination | pages/components/hooks/router/API | typecheck/build; browser reload/workflow/mobile | Đạt các luồng đã ghi |
| UI-SESSION | Hết phiên, giữ nội dung nếu gửi lỗi | app.tsx/api.ts/useDialog | Browser cố ý logout rồi đăng nhập lại | Đạt theo E2E (giữ nguyên textarea sau đăng nhập lại) |
| SEED / gốc§12, tiếp§9 | 100 hồ sơ đúng chuỗi, số liệu/mốc/ratings/files | FeedbackSeeder/demo:verify | demo-statistics, clean-install-check, seed lần hai | Đạt |
| UTC / gốc§5, tiếp§3/9 | Lưu UTC, hiển thị Việt Nam, timestamp hợp lý | DB timezone, RecordsCreatedAt, Intl | WorkflowTest epoch; seed integrity; ảnh browser | Đạt |
| QUALITY / gốc§13, tiếp§10 | Tests MySQL, typecheck/build/E2E riêng | tests/phpunit/playwright/tsconfig | JUnit + Playwright JSON, TEST_REPORT | Đạt lượt đã chạy |
| DOC / gốc§14, tiếp§11 | Setup/walkthrough/SRS/ERD/diagrams/ADR/API/FAQ/demo | Bộ docs và README | verify-openapi, đối chiếu file/test, dictionary tự xuất | Đạt bản local |
| RESTORE / gốc§15, tiếp§11–12 | Sao lưu và thử khôi phục riêng | SETUP/backups | DB restore riêng COUNT/AVG/FK | Đạt kiểm tra cơ bản |
| DOCKER / tiếp§3 | Compose nếu môi trường cho phép | docker-compose.yml + init SQL | Không có Docker CLI | Có cấu hình, chưa chạy |
| PERF / gốc§13, tiếp§10 | p95 <2s, 50 người/10k | QUERY_PLANS chỉ dataset nhỏ | Chưa benchmark | Chưa kiểm chứng, không tuyên bố đạt |

Factory nền: UserFactory, DepartmentFactory, CategoryFactory, TicketFactory; fixture kiểm quyền khóa/sai vai trò/thành viên ngừng/ADMIN+COORDINATOR tạo riêng trong tests, không thêm vào 26 tài khoản demo.

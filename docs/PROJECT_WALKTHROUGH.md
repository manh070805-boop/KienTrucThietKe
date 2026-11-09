# Hiểu project từ một hồ sơ thật

## Ứng dụng giải quyết việc gì?

Sinh viên gửi phản ánh/kiến nghị đến trường; điều phối giao cho cán bộ; cán bộ trao đổi, xử lý; sinh viên xác nhận kết quả. Khác với hộp thư góp ý, hệ thống giữ được người đang phụ trách, hạn xử lý và từng lần thay đổi.

Frontend là phần chạy trong trình duyệt: React vẽ màn hình và gửi yêu cầu. Backend là Laravel: kiểm tra người dùng được phép làm gì và thực hiện nghiệp vụ. Database MySQL lưu dữ liệu bền vững; tải lại trang không làm mất hồ sơ đã lưu. API là hợp đồng trao đổi JSON giữa hai phần.

## Mở mã theo thứ tự này

| Tệp/thư mục | Cần hiểu gì |
|---|---|
| routes/web.php, resources/views/app.blade.php | Mọi deep link SPA nhận cùng trang React |
| resources/js/app.tsx, router.tsx | Xác thực ban đầu và chọn page theo URL |
| resources/js/pages/DraftEditor.tsx | Lưu nháp rồi submit, không gửi lại nội dung trong submit |
| resources/js/api.ts | Cookie, CSRF, lỗi 401/403/409/422, không lưu token localStorage |
| routes/api.php | Endpoint biểu thị hành động; không có đổi status tùy ý |
| app/Http/Requests/TicketRequest.php | Các trường hợp lệ cho mỗi hành động |
| app/Policies/TicketPolicy.php, Support/Access.php | Quyền hành động và phạm vi đọc |
| app/Services/TicketService.php | Một chỗ thực hiện quy trình và transaction |
| app/Domain/Tickets/Workflow.php | Bảng chuyển trạng thái |
| app/Models/Ticket.php | Quan hệ student/category/messages/history/assignments/rating |
| app/Http/Resources/TicketResource.php | Chỉ trả dữ liệu thuộc quyền; cùng hợp đồng list/detail |
| app/Services/AttachmentService.php | Tệp riêng tư và bù trừ lỗi filesystem |
| app/Services/ReportingService.php, TicketQuery.php | Thống kê và bộ lọc/phạm vi chung |
| app/Services/AdminService.php | Quản trị dữ liệu nền và ghi audit |
| database/migrations | Thiết kế schema chuẩn |
| database/seeders/FeedbackSeeder.php | Dataset được tạo qua use case, có thời gian hợp lý |
| tests/Feature | Test chứng minh quy tắc, không chỉ test trang 200 |

## Ví dụ: sinh viên không đăng nhập được cổng học tập

1. Mở /tickets/new, chọn “Tài khoản sinh viên”, nhập tiêu đề/nội dung và lưu nháp. DraftEditor tạo client_request_id bằng crypto.randomUUID, giữ UUID trong sessionStorage cho retry. Đây là khóa chống tạo trùng, không phải token đăng nhập.
2. api.ts gửi POST /api/tickets. Web middleware đọc cookie phiên, kiểm tra CSRF. Auth/active chặn người chưa đăng nhập hoặc bị khóa. TicketRequest chỉ nhận UUID và type.
3. TicketService::create khóa bản ghi sinh viên trong transaction, kiểm tra Policy, tìm UUID đã có. Nếu retry thì trả cùng hồ sơ; nếu mới thì tạo DRAFT cùng lịch sử khởi tạo và audit.
4. PUT /api/tickets/{id} lưu tiêu đề, nội dung và danh mục. Transaction khóa hồ sơ, kiểm tra chủ sở hữu và version, chỉ cho sửa DRAFT, tăng version.
5. Sinh viên tải ảnh lên. AttachmentService kiểm tra nội dung ảnh, khóa cùng hồ sơ để giới hạn ba tệp, lưu ngoài public rồi ghi metadata. Nếu ghi metadata lỗi thì xóa file vừa ghi.
6. Bấm “Gửi hồ sơ”. Frontend lưu nháp trước rồi POST /submit chỉ có version. Service đọc nội dung đã lưu, kiểm tra danh mục/đơn vị hoạt động, sinh mã ngẫu nhiên duy nhất và chốt due_at = thời điểm gửi + resolution_days. Lưu history, thông báo và audit trước commit.
7. Điều phối thấy hồ sơ qua GET /tickets với Access::scope. ADMIN thuần thấy danh sách rỗng vì không có nhánh cấp quyền đọc. Điều phối chọn đơn vị CNTT và staff4; Service kiểm tra thành viên/tài khoản/vai trò rồi ghi phân công.
8. Cán bộ bấm bắt đầu; chỉ người hiện được giao và đang có quyền STAFF được phép. Cán bộ yêu cầu bổ sung: tin nhắn INFO_REQUEST và WAITING_STUDENT cùng transaction.
9. Sinh viên gửi bổ sung: INFO_RESPONSE và IN_PROGRESS cùng transaction. Ghi chú nội bộ không xuất hiện trong JSON của sinh viên, kể cả tệp của ghi chú.
10. Cán bộ gửi RESOLUTION → RESOLVED. Sinh viên kiểm tra, yêu cầu xử lý lại nếu còn lỗi (cần lý do, xóa resolved_at hiện tại nhưng giữ tin nhắn cũ), hoặc xác nhận CLOSED rồi đánh giá một lần.

## Những thuật ngữ cần giải thích khi bảo vệ

- Policy: hàm quyết định ai được làm một hành động trên một hồ sơ.
- Scope: điều kiện SQL giới hạn tập hồ sơ nhìn thấy. Policy và scope dùng chung Access để thống nhất.
- Transaction: nhóm thay đổi phải cùng thành công hoặc cùng quay lại trạng thái trước.
- Khóa bi quan (lockForUpdate): giữ hàng MySQL trong lúc cập nhật, để hai tiến trình không sửa cùng lúc.
- Version: số phiên bản tăng mỗi cập nhật. Client gửi số cũ thì trả 409 thay vì âm thầm ghi đè.
- Idempotency: gửi lại cùng yêu cầu hợp lệ không tạo tác dụng lặp, như hai hồ sơ cùng UUID.
- Resource/DTO: danh sách trường được cho phép trả ra API; không trả Eloquent Model nguyên trạng.
- Audit: nhật ký ai thực hiện use case nào. Không ghi password/cookie/token/nội dung trao đổi vào metadata.
- SLA: số ngày xử lý cam kết tại lần gửi. Chờ sinh viên không tự dừng hoặc gia hạn SLA trong MVP.

## Cách tìm bằng chứng thay vì học thuộc

Muốn giải thích sinh viên không tự bắt đầu: mở TicketPolicy::act (start chỉ Access::staff), TicketService::execute (Policy chạy sau lock), rồi test_student_cannot_start_resolve_or_request_information.

Muốn chứng minh cán bộ cũ mất quyền: mở Access::scope/staff và AuthorizationTest::test_transfer_removes_old_staff_file_notification_and_reporting_access; AttachmentTest kiểm tra cả file nội bộ.

Muốn chứng minh rollback: WorkflowTest cố ý làm lỗi tạo history sau khi tạo tin nhắn; test xác nhận cả message, status và version đều không đổi. ConcurrencyTest dùng hai tiến trình PHP thực, cùng version; chỉ một thành công, một trả 409.

Không nói “React ẩn nút nên an toàn”. Nút chỉ gợi ý; backend luôn kiểm tra lại dữ liệu hiện tại trong transaction.

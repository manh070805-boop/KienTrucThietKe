# ADR — quyết định kiến trúc

Ngày: 28/10/2026. Trạng thái: áp dụng trong bản đồ án local.

## ADR-01: Giữ Laravel và Modular Monolith

Một tiến trình ứng dụng, một schema MySQL. Chia trách nhiệm Identity, Organization, Tickets, Attachments, Notifications, Reporting bằng các Service/Policy/Request/Resource đã liệt kê trong DIAGRAMS.md. Giữ thư mục Laravel kế thừa để sinh viên dễ tra cứu; chỉ Domain/Tickets chứa bảng workflow. Chưa có package boundary kiểm tra tự động; tránh mô tả là microservices. Không thêm Repository bọc lại Eloquent vì hiện chưa có hai nguồn lưu trữ cần thay thế.

## ADR-02: Session/cookie cùng origin

Routes API đi qua web middleware; Laravel session guard đủ cho SPA cùng origin nên không cài Sanctum chỉ để tạo token. CSRF trước mọi mutation; sau login lấy token mới vì regenerate session cũng đổi CSRF token. Cookie phiên HttpOnly + SameSite Lax, không token localStorage. HTTP local để Secure=false; HTTPS thực tế phải cấu hình lại. Throttle login theo email+IP; tài khoản ngừng hoạt động bị chặn cả phiên đã đăng nhập.

Nguồn: [Laravel 12 authentication](https://laravel.com/docs/12.x/authentication), [session](https://laravel.com/docs/12.x/session), [CSRF](https://laravel.com/docs/12.x/csrf).

## ADR-03: Quyền được cấp rõ, mặc định từ chối

Access::scope bắt đầu bằng 1=0 rồi OR các quyền STUDENT/COORDINATOR/STAFF. Nhiều vai trò là hợp quyền theo use case, không ghép các AND làm mất quyền hợp lệ. TicketPolicy chạy lại sau khóa ticket. ADMIN không có Gate::before cho phép đọc mọi hồ sơ. Resource lọc message/tệp nội bộ ngay ở server. Thông báo và download kiểm tra quyền hiện tại, không dựa trên assignment cũ.

Hệ quả: quản trị muốn điều phối phải có thêm COORDINATOR. Cán bộ cũ mất quyền ngay cả thông báo đã được tạo trước khi chuyển. Tài khoản đa vai trò được test riêng.

## ADR-04: Workflow tập trung, nội dung gốc bất biến

Workflow::TRANSITIONS khai báo những cạnh hợp lệ. Không API đặt status bất kỳ. Submit chỉ nhận version, lấy nội dung nháp đã lưu; classify đổi danh mục qua use case riêng và audit, giữ due_at. Chờ bổ sung/chuyển/xử lý lại không gia hạn. RESOLVED là cán bộ đã trả lời; CLOSED cần chủ hồ sơ xác nhận. Reopen chỉ từ RESOLVED, xóa resolved_at hiện tại, giữ tin nhắn/kết quả cũ.

## ADR-05: Transaction + lock + version + idempotency

Mỗi mutation: transaction → khóa ticket → Policy/trạng thái/version → cập nhật/version++ → dữ liệu liên quan → commit. Tạo nháp khóa hàng sinh viên để serialize cùng UUID, UNIQUE(student_id, client_request_id) là lớp bảo vệ DB. Submit lưu submitted_version là version đầu vào trước lần gửi thành công; retry đúng version này trả trạng thái hiện tại kể cả đã chuyển sang bước tiếp, không nhân history/mã/notify.

Mã hồ sơ là ngày + 12 ký tự hex ngẫu nhiên, có UNIQUE, không MAX(id)+1. Xác suất va chạm thấp; chưa có retry chuyên biệt cho va chạm mã cực hiếm. Các transaction DB thuần có tối đa 3 lần retry deadlock; transaction ghi filesystem không tự retry.

Bằng chứng: ConcurrencyTest dùng hai tiến trình MySQL/PHP cùng version; một thành công, một 409. Test rollback cố ý gây lỗi history sau tạo message.

Nguồn: [Laravel database transactions](https://laravel.com/docs/12.x/database#database-transactions), [pessimistic locking](https://laravel.com/docs/12.x/queries#pessimistic-locking).

## ADR-06: Tệp private và bù trừ filesystem

Tên lưu do server sinh; fileinfo nhận MIME, getimagesize xác minh ảnh, PDF kiểm header/EOF; size 1–5 MB và ba tệp mỗi nội dung. Khóa ticket cùng version để kiểm số lượng upload. File nằm ngoài public, không storage:link; endpoint download kiểm Policy và visibility của message, ép attachment/nosniff/no-store.

Filesystem không tham gia transaction MySQL. Lỗi metadata → catch xóa file đã ghi. Crash giữa ghi file/commit có thể để orphan; files:cleanup mặc định chỉ liệt kê, --delete chỉ dọn file >24 giờ và không có metadata. Xóa tệp nháp commit metadata rồi xóa file, nếu lỗi đĩa thì dọn orphan sau. Không có antivirus hoặc PDF parser đầy đủ; không mô tả kiểm tra cơ bản này là quét mã độc.

## ADR-07: MySQL 8.4 là nguồn kiểm chứng dữ liệu

InnoDB, utf8mb4; PK/FK unsigned khớp kiểu. Khóa tham chiếu ghép có UNIQUE tường minh theo quy tắc MySQL 8.4. CHECK cho enum/score/SLA/phân công/nội dung đã gửi/metadata tệp. Migration bổ sung thay vì reset schema đang dùng. Tệp SQL là bản xuất từ schema migrate; tài liệu cột/EXPLAIN cũng xuất từ DB. SQLite cũ chỉ lưu tham khảo.

Nguồn: [MySQL 8.4 foreign keys](https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html), [CHECK](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html).

## ADR-08: Giao diện tham khảo Lattice

Nguồn công khai [getdesign.md/Lattice](https://getdesign.md/design-md/lattice) mô tả xanh #046645 và nền trắng. Dùng các màu này cùng card, form, khoảng cách, góc bo và typography thống nhất trong resources/css/app.css. Phông hệ thống Inter/Segoe UI dùng khi có sẵn, không phân phối Matter thương mại. Đây là ứng dụng lấy cảm hứng phong cách, không sao chép toàn bộ template trả phí.

React custom hook giữ fetch state và cleanup để response cũ không ghi đè trang mới. TypeScript kiểm kiểu bằng tsc --noEmit riêng với Vite build. Router nhỏ dùng History API, popstate và catch-all Laravel; hỗ trợ deep link/reload.

Laravel Blade có @viteReactRefresh trước @vite, đã chạy smoke HMR theo [Laravel Vite/React](https://laravel.com/docs/12.x/vite#react).

Nguồn: [React useEffect](https://react.dev/reference/react/useEffect), [TypeScript noEmit](https://www.typescriptlang.org/tsconfig/noEmit.html).

# Câu hỏi bảo vệ đồ án

## Vì sao Modular Monolith?

Một ứng dụng được chia theo trách nhiệm nghiệp vụ, dùng chung database và triển khai cùng nhau. Nhóm nhỏ dễ cài, test transaction và theo dõi luồng hơn nhiều service mạng. IdentityService, AdminService, TicketService, AttachmentService, TicketQuery/ReportingService là các đầu mối thực tế. Chưa có package isolation tự động; đây là lựa chọn phù hợp phạm vi đồ án, không gọi là microservices.

## RESOLVED khác CLOSED thế nào?

RESOLVED nghĩa là cán bộ đã gửi kết quả, sinh viên chưa xác nhận. Chủ hồ sơ có thể yêu cầu xử lý lại kèm lý do. CLOSED chỉ xảy ra khi chủ xác nhận; sau đó được đánh giá một lần và không mở lại trong MVP. Xem Workflow và TicketPolicy.

## Tại sao phải giữ lịch sử?

Một dòng tickets chỉ cho biết hiện tại. History trả lời “đã đi qua những bước nào”, assignments trả lời “đã chuyển cho ai”, messages giữ các lần trả lời/bổ sung. Nhờ đó xử lý lại không làm mất kết quả cũ. API không có sửa/xóa nhật ký; model chặn cập nhật/xóa thông thường.

## Transaction và version có trùng nhiệm vụ không?

Transaction bảo đảm message, status, assignment, history, notification/audit cùng thành công. lockForUpdate giữ hàng cho một giao dịch cập nhật tại một thời điểm. Version phát hiện người dùng đang xem bản cũ và trả 409. Hai tiến trình vẫn cần version để tiến trình chờ khóa không ghi đè dữ liệu mới. ConcurrencyTest chứng minh một 200 và một 409.

## Chống gửi lặp thế nào?

Tạo nháp giữ cùng UUID cho một lần tạo, UNIQUE theo sinh viên; Service khóa hàng sinh viên và trả lại hồ sơ có sẵn. Submit không nhận lại nội dung, lưu submitted_version đầu vào; retry đúng lần gửi trả hồ sơ hiện tại, không tạo mã/history/notify nữa. Retry không đồng nghĩa cho phép mọi version cũ.

## Lỗi giữa lúc sinh viên bổ sung thì sao?

Tạo INFO_RESPONSE và đổi WAITING_STUDENT → IN_PROGRESS cùng transaction, sau khi kiểm tra Policy/trạng thái/version. Test cố ý làm bước tạo history thất bại và kiểm tra tin nhắn, trạng thái, version đều không thay đổi.

## Khóa ngoại ghép có ích gì?

tickets(assigned_department_id, assigned_staff_id) tham chiếu cặp UNIQUE của department_members để cán bộ thuộc đúng đơn vị. attachments(message_id, ticket_id) tham chiếu UNIQUE(id, ticket_id) của ticket_messages để không gắn file sang hồ sơ khác. MySQL bảo vệ quan hệ; Service vẫn kiểm tra active và role, vì FK không biết vai trò.

## Vì sao ADMIN không được đọc mọi phản ánh?

Quản trị tài khoản/dữ liệu nền là nhiệm vụ khác với xử lý nội dung của sinh viên. Quyền đọc cấp rõ qua STUDENT/STAFF/COORDINATOR; ADMIN muốn điều phối phải được gán thêm COORDINATOR. Không có nhánh “admin luôn đúng” trong Gate/Policy.

## Ẩn nội bộ bằng CSS có đủ không?

Không. Người dùng có thể đọc network response. TicketResource loại message/tệp nội bộ khỏi JSON của sinh viên. Download và notifications kiểm tra quyền hiện tại; cán bộ cũ mất quyền sau chuyển. Test cả nội dung lẫn endpoint file.

## Rollback database có xóa tệp trên ổ đĩa không?

Không. AttachmentService tự xóa tệp vừa ghi nếu metadata lỗi. Nếu tiến trình crash hoặc xóa file thất bại thì files:cleanup dọn orphan sau thời gian an toàn 24 giờ, mặc định chỉ xem trước. Không dùng URL public hay storage:link cho phản ánh.

## Tại sao due_at không đổi khi chờ sinh viên/chuyển/xử lý lại?

SLA được chốt từ danh mục tại lần gửi để tránh mỗi lần thay đổi nghiệp vụ lại tự gia hạn. Trong MVP mọi khoảng chờ vẫn tính ngày lịch. Nếu cần cơ chế tạm dừng hạn phải thêm use case và lịch sử riêng, không sửa lén due_at.

## Tại sao không dùng SQLite để kiểm thử?

SQLite cũ chỉ là bản demo tham khảo. MySQL 8.4 có ràng buộc/khóa/transaction và yêu cầu khóa tham chiếu riêng. Tests chạy thật trên student_feedback_test, kiểm FK/UNIQUE/CHECK, cạnh tranh hai tiến trình và UTC session. Không lấy test in-memory để suy ra locking MySQL.

## Vì sao vẫn cần tsc khi Vite build thành công?

Vite chuyển TSX thành JavaScript nhưng không thay thế kiểm kiểu đầy đủ. npm run typecheck chạy tsc --noEmit; npm run build kiểm bundling riêng. Browser E2E kiểm tương tác/CSRF/dữ liệu qua UI, là lớp kiểm tra khác.

## Cách bảo đảm UTC?

Kết nối MySQL đặt time_zone +00:00. Model nhật ký không có updated_at dùng RecordsCreatedAt gán now() từ ứng dụng, tránh CURRENT_TIMESTAMP theo timezone hệ điều hành và cho phép clock cố định trong seed/test. Giao diện dùng Intl với Asia/Ho_Chi_Minh. Có test so epoch UNIX_TIMESTAMP với clock ứng dụng, không chỉ so chuỗi hiển thị.

## 100 hồ sơ và điểm 3,75 được chứng minh ra sao?

Seed tạo qua use case có lịch sử đầy đủ. demo:verify kiểm trạng thái, quá hạn tại anchor, phân bố điểm, chuỗi lịch sử, thời gian, SLA và MIME/metadata tệp. 45 điểm chia 12 lượt = 3,75; 4 CLOSED chưa đánh giá không được coi là 0 điểm.

## Còn giới hạn nào?

Chưa đo p95 50 người/10.000 hồ sơ; Docker chỉ có cấu hình chưa chạy; chưa có SSO/email/antivirus/PDF parser đầy đủ; chưa chứng nhận production. Không autosave form nền, nội dung chưa lưu có thể mất khi rời trang/đổi tài khoản; đăng nhập lại cùng tài khoản sau hết phiên vẫn giữ form đang mở. Lịch sử chặn ở tầng ứng dụng/API, người có quyền SQL quản trị vẫn có thể sửa nên cần kiểm soát vận hành khi triển khai thật.

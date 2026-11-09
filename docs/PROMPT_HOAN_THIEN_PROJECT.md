# Prompt hoàn thiện project KienTrucThietKe

Sao chép toàn bộ nội dung trong khối bên dưới để giao nhiệm vụ triển khai. Đây là yêu cầu công việc tiếp theo, không phải xác nhận rằng các chức năng đều đã hoàn thành.

```text
Bạn là kỹ sư phần mềm cấp cao kiêm người hướng dẫn đồ án. Hãy trực tiếp hoàn thiện project “HỆ THỐNG TIẾP NHẬN PHẢN ÁNH VÀ KIẾN NGHỊ CỦA SINH VIÊN” trong:

D:/laragon/www/KienTrucThietKe

Mục tiêu: ứng dụng chạy đầy đủ với dữ liệu mẫu, đúng phân quyền và nghiệp vụ, có kiểm thử và tài liệu giúp tôi hiểu mã nguồn, giải thích luồng và bảo vệ đồ án. Hãy triển khai đến khi đạt tiêu chí nghiệm thu trong phạm vi được phép; không chỉ đưa kế hoạch hoặc hoàn thiện dashboard rồi dừng.

1. CÁCH LÀM VIỆC

- Đọc AGENTS.md nếu có, kiểm tra Git, mã nguồn, dependencies, môi trường và database trước khi sửa. Bảo toàn thay đổi có sẵn. Không dựng lại hoặc đổi framework khi phần hiện tại vẫn phù hợp.
- Đọc lại yêu cầu gốc trong attachment nếu truy cập được: C:/Users/manh0/.codex/attachments/61bcb1c6-3409-4a84-93ca-c48cff7bc36a/pasted-text.txt. Nội dung bên dưới là đặc tả tiếp tục triển khai; đối chiếu các yêu cầu chi tiết của attachment và lập bảng truy vết.
- Tự xử lý lựa chọn kỹ thuật thông thường. Chỉ hỏi nếu thiếu thông tin thực sự ngăn cản triển khai hoặc cần thao tác phá hủy dữ liệu.
- Không xóa/reset database đang có, không chạy migrate:fresh trên database người dùng. Dùng database phát triển và test riêng; sao lưu trước chuyển đổi dữ liệu.
- Giao diện và tài liệu tiếng Việt; mã nguồn, bảng, cột tiếng Anh. Dữ liệu thật của giao diện phải đi qua API và lưu database.
- Mỗi giai đoạn báo ngắn: đã làm gì, kiểm chứng bằng gì, còn thiếu gì; sau đó tiếp tục phần tiếp theo. Không tuyên bố test đạt nếu chưa chạy.
- Đọc tài liệu chính thức và kiểm tra phiên bản thực tế trước khi dùng API phụ thuộc framework. Không nâng major version nếu không cần thiết.
- Thay đổi chỉ ở project và tài nguyên phục vụ project. Không tự push, merge hoặc công khai triển khai.

2. KIỂM TRA VÀ SỬA NHỮNG ĐIỂM HIỆN TẠI

Tại lần rà soát ngày 28/10/2026, project có Laravel 12, React/TSX, migrations/models, enums, AuthController, DashboardController, DirectoryController, TicketController, NotificationController, Access helper và FeedbackSeeder. Đây là thông tin định hướng; hãy kiểm tra lại trạng thái thực tế trước khi sửa.

Các điểm cần đối chiếu, sửa và kiểm thử:
- .env đang dùng SQLite; có nhiều file database demo. Không mặc định chúng là cùng một nguồn dữ liệu. Tài liệu hóa database đang chạy và chuyển bản hoàn thiện sang MySQL đúng yêu cầu.
- TicketController và DashboardController lọc theo các nhánh vai trò; phải bảo đảm ADMIN thuần và người dùng không có quyền không rơi vào truy vấn không giới hạn.
- Quyền chuyển trạng thái hiện chưa được tách chặt theo từng hành động. Sinh viên không được gọi start chỉ vì là chủ hồ sơ; cán bộ phải hiện được giao, có vai trò hợp lệ và tài khoản hoạt động.
- submit đang nhận lại nội dung; cần dùng bản nháp đã lưu làm nguồn, bảo vệ nội dung gốc và sửa retry/version bên trong transaction.
- Tạo tin nhắn bổ sung và đổi WAITING_STUDENT sang IN_PROGRESS phải cùng transaction; thất bại không để lại tin nhắn dở dang.
- API danh sách đang trả trực tiếp Model còn API chi tiết có serializer riêng: thống nhất Resource/DTO và hợp đồng frontend/backend.
- Lịch sử nghiệp vụ hiển thị cho sinh viên cần loại nội dung nội bộ, không được vì thế xóa toàn bộ tiến độ hợp lệ của sinh viên.
- Chưa có đầy đủ API/giao diện quản trị, tệp riêng tư, báo cáo, sửa bản nháp, đánh giá và ghi chú nội bộ. Kiểm tra và bổ sung cả frontend lẫn backend.
- Seed hiện tạo trạng thái và lịch sử chưa đầy đủ chuỗi; chưa có đầy đủ dữ liệu đánh giá/tệp/kịch bản xử lý lại. Viết lại seed có kiểm chứng và chạy lặp an toàn.
- Tests hiện chủ yếu là ExampleTest; README còn mô tả khởi tạo. Bổ sung test nghiệp vụ và tài liệu đúng bản triển khai.
- Kiểm tra composer autoload, cấu hình bootstrap, TypeScript/typecheck, scripts run.bat/dev.bat, đường dẫn PHP Laragon và lỗi API chưa đăng nhập. Không coi vite build là đã kiểm tra TypeScript hay đã kiểm thử end-to-end.

3. CÔNG NGHỆ VÀ KIẾN TRÚC

- Backend Laravel/PHP tương thích dependency hiện có; frontend React + TypeScript + Vite.
- Database chính MySQL 8.4, InnoDB, utf8mb4. Tạo student_feedback cho môi trường phát triển sạch và student_feedback_test cho kiểm thử. Nếu tên đã có dữ liệu, chọn tên riêng và ghi rõ; không reset.
- SQLite có thể giữ để tham khảo demo cũ, nhưng không dùng kết quả SQLite để khẳng định FK, CHECK, locking hoặc transaction MySQL hoạt động đúng.
- Một trường, nhiều đơn vị; Modular Monolith gồm Identity, Organization, Tickets, Attachments, Notifications, Reporting.
- Controller nhận/trả dữ liệu; FormRequest validate; Policy phân quyền; Action/Service xử lý use case; cơ chế chuyển trạng thái tập trung; Model quản lý quan hệ; Resource/DTO kiểm soát response. Không nhồi nghiệp vụ trong Controller hoặc React.
- Cấu trúc thư mục lựa chọn theo hiện trạng và giải thích. Không thêm Repository chỉ để bọc lại Eloquent.
- Đăng nhập SPA cùng origin bằng session/cookie phù hợp Laravel; dùng Sanctum stateful nếu thực sự cần. Session cookie HttpOnly, bảo vệ CSRF, cấu hình CORS đúng môi trường, không lưu token trong localStorage, có giới hạn thử đăng nhập.
- Lưu UTC, hiển thị Asia/Ho_Chi_Minh. Cung cấp Docker Compose nếu môi trường cho phép, đồng thời giữ cách chạy bằng Laragon.

4. DATABASE VÀ TÍNH NHẤT QUÁN

Hoàn thiện 14 bảng nghiệp vụ: users, roles, user_roles, departments, department_members, categories, tickets, ticket_messages, attachments, ticket_assignments, ticket_status_history, notifications, ticket_ratings, audit_logs. Liệt kê riêng bảng kỹ thuật Laravel.

- Migrations là nguồn thiết kế. Ưu tiên migration bổ sung cho schema đã dùng; schema.sql xuất/đối chiếu từ migrations.
- Khóa chính/ngoại BIGINT UNSIGNED khớp kiểu. Email, student_code, code, client_request_id và rating có UNIQUE đúng yêu cầu gốc.
- tickets UNIQUE(student_id, client_request_id); UUID được tạo một lần cho một lần tạo nháp và tái sử dụng khi retry. Sinh mã hồ sơ khi gửi, không dùng MAX(id)+1.
- Cán bộ hiện tại phải thuộc đơn vị: FK ghép vào UNIQUE(department_id, user_id) của department_members. Không đặt UNIQUE cặp phân công trên tickets vì nhiều hồ sơ có thể giao cùng cán bộ.
- Cặp phân công cùng NULL hoặc cùng có giá trị. Trạng thái ASSIGNED, IN_PROGRESS, WAITING_STUDENT, RESOLVED, CLOSED phải có phân công hợp lệ; bổ sung các CHECK phù hợp.
- Chỉ phân công tài khoản STAFF hoạt động, thành viên hoạt động của đơn vị hoạt động; kiểm tra ở nghiệp vụ bên cạnh FK.
- attachments(message_id, ticket_id) phải tham chiếu đúng tin nhắn cùng hồ sơ, với khóa UNIQUE tham chiếu tường minh tương thích MySQL 8.4.
- CHECK tập giá trị type/status/kind/visibility, score 1–5, resolution_days > 0 và giới hạn metadata tệp phù hợp.
- Không cascade xóa hồ sơ/tin nhắn/lịch sử khi tài khoản hoặc danh mục ngừng hoạt động. Không có sửa/xóa lịch sử thông thường.
- Có index phục vụ chủ hồ sơ, trạng thái, cán bộ, đơn vị, due_at, dòng thời gian, thông báo; kiểm tra EXPLAIN truy vấn thực tế.
- Tạo ERD, từ điển dữ liệu, factories, seeders, hướng dẫn backup/restore và kiểm thử FK/UNIQUE/CHECK trên MySQL.

5. PHÂN QUYỀN

STUDENT: tạo/sửa nháp của mình, gửi hồ sơ, xem hồ sơ/tệp của mình, trao đổi và bổ sung, rút khi chưa phân công, đóng hoặc yêu cầu xử lý lại khi RESOLVED, đánh giá sau CLOSED; không đọc nội dung nội bộ.

COORDINATOR: xem hồ sơ đã gửi, không xem nháp; phân loại, phân công, chuyển đơn vị, từ chối SUBMITTED có lý do, xem lịch sử/nội bộ và báo cáo trong phạm vi quyền.

STAFF: chỉ đọc/xử lý hồ sơ hiện được giao; bắt đầu, trao đổi, yêu cầu bổ sung, ghi chú nội bộ, trả lời chính thức. Sau chuyển xử lý cán bộ cũ mất quyền dựa trên phân công cũ, kể cả tải tệp và thông báo có nội dung liên quan.

ADMIN: quản lý tài khoản, vai trò, đơn vị, thành viên, danh mục. Không mặc nhiên được xem nội dung phản ánh; muốn điều phối phải gán thêm COORDINATOR.

Áp dụng Policy/scope nhất quán cho list, detail, actions, reporting, notifications và files. Người nhiều vai trò được tính quyền rõ ràng theo từng use case. Mặc định từ chối nếu không có quyền phù hợp. Không gửi dữ liệu nội bộ ra frontend rồi chỉ dùng CSS để ẩn.

6. QUY TRÌNH NGHIỆP VỤ

Loại hồ sơ: FEEDBACK, PROPOSAL.

Chuyển trạng thái hợp lệ:
- DRAFT -> SUBMITTED: sinh viên gửi nháp hợp lệ.
- SUBMITTED -> ASSIGNED: điều phối phân công.
- SUBMITTED -> REJECTED: điều phối từ chối, bắt buộc lý do.
- SUBMITTED -> CANCELLED: chủ hồ sơ rút.
- ASSIGNED -> IN_PROGRESS: cán bộ hiện được giao bắt đầu.
- IN_PROGRESS -> WAITING_STUDENT: cán bộ yêu cầu bổ sung có nội dung.
- WAITING_STUDENT -> IN_PROGRESS: chủ hồ sơ gửi bổ sung.
- IN_PROGRESS -> RESOLVED: cán bộ gửi kết quả chính thức.
- RESOLVED -> CLOSED: chủ hồ sơ xác nhận.
- RESOLVED -> IN_PROGRESS: chủ hồ sơ yêu cầu xử lý lại có lý do.

Không có API đổi status tùy ý. Không tự đóng; không mở lại CLOSED trong MVP.

- Hồ sơ đã gửi giữ nguyên nội dung gốc. Bổ sung qua tin nhắn. Phân loại lại do điều phối được ghi audit, giữ due_at.
- Chuyển phân công chỉ khi ASSIGNED/IN_PROGRESS, bắt buộc lý do, người nhận hợp lệ, đưa về ASSIGNED. Lưu lịch sử phân công kể cả trạng thái trước/sau đều ASSIGNED.
- due_at chốt từ resolution_days tại lần gửi, mặc định 7 ngày lịch; thay danh mục, chờ bổ sung, chuyển đơn vị hoặc xử lý lại không tự gia hạn.
- Xử lý lại xóa resolved_at hiện tại nhưng giữ kết quả và lịch sử các lần trước.
- Tin nhắn có COMMENT, INFO_REQUEST, INFO_RESPONSE, RESOLUTION; visibility STUDENT_VISIBLE hoặc STAFF_ONLY. Sinh viên không được giả loại/vai trò/nội dung nội bộ của cán bộ.

Với cập nhật nghiệp vụ: transaction -> khóa hồ sơ -> kiểm tra quyền, trạng thái, version -> cập nhật/tăng version -> lưu history, assignment, message, notification/audit liên quan -> commit; lỗi phải rollback.

Version cũ trả 409. Retry hợp lệ tạo nháp/gửi hồ sơ không nhân đôi dữ liệu; xử lý trường hợp submit đã thành công mà client không nhận được response. Kiểm tra cạnh tranh bằng test phù hợp MySQL.

7. TỆP, THÔNG BÁO VÀ BÁO CÁO

Tệp:
- PDF/JPG/PNG, tối đa 5 MB/tệp; tối đa 3 tệp nội dung gốc và 3 tệp mỗi tin nhắn.
- Validate nội dung thực ở server, tên lưu do server sinh, lưu ngoài public; tải qua endpoint có Policy.
- Nội dung gốc chỉ bổ sung/xóa tệp khi còn nháp; quyền tệp nội bộ kế thừa tin nhắn; kiểm tra giới hạn khi upload đồng thời.
- Xử lý tệp tạm, lỗi ghi metadata và dọn tệp mồ côi. Rollback MySQL không tự rollback filesystem.

Thông báo: thông báo trong hệ thống cho sự kiện quan trọng, đánh dấu đã đọc, không rò ghi chú nội bộ hoặc cho phép vượt quyền sau chuyển xử lý.

Báo cáo: lọc theo khoảng ngày gửi, loại, trạng thái, danh mục, đơn vị; có tổng đã gửi, số lượng theo nhóm, đang mở quá hạn, điểm trung bình và số lượt đánh giá. Không tính DRAFT vào tổng đã gửi. Quá hạn chỉ gồm SUBMITTED/ASSIGNED/IN_PROGRESS/WAITING_STUDENT với now > due_at. Trung bình chỉ tính hồ sơ có đánh giá. Phạm vi quyền dùng chung với hồ sơ; bổ sung xuất CSV nếu phù hợp.

8. FRONTEND ĐẦY ĐỦ

Giữ hướng thiết kế người dùng đã chọn: https://getdesign.md/design-md/lattice. Đọc nguồn tham chiếu, dùng nhất quán màu sắc, typography, spacing, card, table, form và trạng thái; không tự nhận sao chép nguyên template nếu chỉ tham khảo phong cách.

Tách app.tsx thành component, page, API client, hook và type phù hợp; có routing, deep link và tải lại trang không mất màn hình. Thiết lập TypeScript typecheck thực sự bên cạnh vite build.

Các màn hình phải hoạt động:
- Đăng nhập, đăng xuất, tài khoản hiện tại.
- Dashboard theo vai trò; ADMIN có thông tin quản trị được phép.
- Yêu cầu của tôi; tạo/lưu/sửa/gửi bản nháp; upload minh chứng.
- Chi tiết: nội dung gốc, người phụ trách, thời hạn, tệp, trao đổi, tiến độ được phép, ghi chú nội bộ theo quyền, đánh giá.
- Hàng đợi tiếp nhận và từ chối có lý do; phân loại, phân công và chuyển đơn vị/cán bộ.
- Công việc được giao; bắt đầu, yêu cầu bổ sung và trả lời chính thức.
- Xác nhận đóng, xử lý lại với lý do tự nhập, đánh giá 1–5 một lần sau đóng.
- Thông báo; báo cáo; quản trị tài khoản/vai trò/đơn vị/thành viên/danh mục.

Menu và hành động theo quyền; server vẫn quyết định cuối cùng. Không hiện nút tạo hồ sơ cho người không có STUDENT. Form thực, validation theo trường, xác nhận thao tác phù hợp; thay window.prompt và lý do cố định bằng form/dialog.

Danh sách có phân trang thật, tìm kiếm/lọc/sắp xếp qua API whitelist; không chỉ lấy 50 dòng rồi coi là toàn bộ. Có loading, empty, success, validation, lỗi mạng, hết phiên, 403 và 409; giữ nội dung đang nhập nếu gửi lỗi. Responsive desktop/mobile, nhãn tiếng Việt và truy cập bằng bàn phím cơ bản.

9. DỮ LIỆU DEMO CÓ THỂ KIỂM CHỨNG

Tạo seed riêng môi trường phát triển, dữ liệu hoàn toàn giả lập, ổn định và chạy lại không làm nhân đôi hồ sơ hoặc ghi đè dữ liệu người dùng. Không tạo bằng cách random status rồi bỏ qua lịch sử bắt buộc.

Dữ liệu nền:
- 4 vai trò.
- 3 đơn vị: Phòng Công tác sinh viên, Phòng Đào tạo, Trung tâm Công nghệ thông tin.
- 6 danh mục: Học bổng và hỗ trợ; Ký túc xá; Lịch học và lớp học; Điểm và kết quả học tập; Tài khoản sinh viên; Cơ sở vật chất.
- 20 sinh viên, 1 điều phối, 4 cán bộ có thành viên đơn vị hợp lệ, 1 ADMIN thuần.
- Tài khoản chính giữ tương thích nếu đã có: student1@demo.local, coordinator@demo.local, staff1@demo.local, admin@demo.local. Password123! chỉ dùng cho môi trường demo, được hash khi lưu.

100 hồ sơ có tên và nội dung cụ thể, khác nhau, phân bố mục tiêu:
DRAFT 8; SUBMITTED 12; ASSIGNED 12; IN_PROGRESS 14; WAITING_STUDENT 12; RESOLVED 12; CLOSED 16; REJECTED 7; CANCELLED 7.

Trong 100 hồ sơ này, không cộng thêm ngoài tổng:
- Đúng 10 hồ sơ đang mở quá hạn tại mốc chạy seed; chốt now cho lần seed và ghi thống kê mong đợi.
- Ít nhất 6 hồ sơ đã từng xử lý lại và 3 hồ sơ có chuyển đơn vị/cán bộ, có history/assignment hợp lệ theo thời gian.
- Trong 16 CLOSED có 12 đánh giá: 1 lượt 1 điểm, 1 lượt 2 điểm, 2 lượt 3 điểm, 4 lượt 4 điểm, 4 lượt 5 điểm. Điểm trung bình toàn bộ là 3,75/5; 4 hồ sơ CLOSED còn lại chưa đánh giá.
- Tin nhắn công khai, yêu cầu/bổ sung, trả lời chính thức, ghi chú nội bộ, thông báo đã đọc/chưa đọc và audit phù hợp.
- Tệp PDF/JPG/PNG minh chứng thực sự mở/tải được, có metadata khớp, lưu private; có tệp cho nháp và ghi chú nội bộ phục vụ demo phân quyền.
- Ngày tạo/gửi/xử lý/đóng có thứ tự hợp lý; due_at đúng SLA lúc gửi. Seed tạo đủ các bước lịch sử dẫn tới trạng thái cuối.

Tạo DEMO_DATA.md: số lượng từng bảng/trạng thái, thống kê mong đợi, tài khoản, mã hồ sơ tiêu biểu cho từng tình huống và dữ liệu mà từng vai trò nhìn thấy. Số 92 hồ sơ đã gửi chỉ áp dụng dataset 100 hồ sơ sạch trên, không áp đặt lên database có dữ liệu khác.

Fixture test cho tài khoản khóa, sai vai trò, thành viên ngừng hoạt động và ADMIN+COORDINATOR tạo riêng. Dữ liệu tải 10.000 hồ sơ là chế độ chủ động riêng, không chạy mặc định.

10. API VÀ KIỂM THỬ

- JSON response và lỗi nhất quán. Phân biệt 401/403/404/422/409. API auth lỗi không trả HTML redirect cho client JSON.
- Các thao tác dùng endpoint action, không nhận status tùy ý; OpenAPI phải khớp bản thực tế.
- Test phân quyền list/detail/action/file/report/notification, đặc biệt sinh viên khác chủ hồ sơ, cán bộ cũ và ADMIN thuần.
- Test tất cả chuyển trạng thái đúng/sai, bản nháp/nội dung gốc, SLA, xử lý lại, ghi chú nội bộ và đánh giá đúng chủ/CLOSED/một lần.
- Test gửi lặp, version cũ, rollback và cạnh tranh không ghi đè âm thầm; FK/UNIQUE/CHECK trên MySQL test riêng.
- Test upload loại/dung lượng/số lượng, tệp nội bộ, ghép tin nhắn khác hồ sơ; thống kê với dataset nhỏ có đáp án biết trước.
- Chạy build frontend, typecheck, test backend và end-to-end qua browser: sinh viên gửi -> điều phối phân công -> cán bộ bắt đầu -> yêu cầu bổ sung -> sinh viên bổ sung -> cán bộ trả lời -> sinh viên đóng -> đánh giá -> dashboard/báo cáo cập nhật.
- Kiểm tra cả 4 vai trò và màn hình mobile. Ghi lệnh, kết quả, lỗi còn lại; không coi HTTP 200 hoặc build thành công là bằng chứng mọi chức năng đã đúng.
- Nếu đo tải, ghi cấu hình/cách đo/kết quả thực tế. Mục tiêu 95% request xem list/detail dưới 2 giây với 50 người và 10.000 hồ sơ không phải kết quả mặc định.

11. TÀI LIỆU VÀ GIẢI THÍCH CHO TÔI

Tạo/cập nhật tài liệu Markdown trong docs/ và README:
- SETUP: phiên bản thực tế, cài đặt, MySQL/env, migrations, seed, lệnh chạy, URL local, tài khoản, xử lý lỗi thường gặp, backup/restore.
- PROJECT_WALKTHROUGH: project giải quyết gì; frontend/backend/database làm gì; thư mục và file quan trọng; giải thích bằng một ví dụ xuyên suốt từ bấm nút đến ghi dữ liệu.
- IMPLEMENTATION_REPORT: phần kế thừa, đã sửa/thêm, lý do, file chính, test chứng minh, giới hạn; phân biệt đã lập trình/đã chạy kiểm chứng/chưa kiểm chứng.
- SRS, ma trận quyền, use case và luồng chính/ngoại lệ; ERD và từ điển dữ liệu.
- Mermaid Activity, State, Class trọng tâm, Sequence cho gửi/phân công/hoàn tất/xử lý lại; C4 Context/Container và Deployment.
- OpenAPI, ADR cho kiến trúc, session/CSRF, quyền, workflow, transaction/version, private files và MySQL.
- TEST_REPORT, bảng truy vết yêu cầu -> mã nguồn/thiết kế -> test -> kết quả.
- USER_GUIDE theo từng vai trò, DEMO_DATA.md, DEMO_SCRIPT.md cho 8–10 phút.
- FAQ_DEFENSE.md: tại sao Modular Monolith, khác nhau giữa RESOLVED/CLOSED, vì sao giữ lịch sử, transaction/version/chống gửi lặp, bảo vệ tệp và dữ liệu nội bộ, quản trị không có quyền đọc mọi hồ sơ.

Giải thích bằng ngôn ngữ sinh viên hiểu, định nghĩa thuật ngữ trước khi dùng, dẫn file và use case thật. Không viết tài liệu về chức năng chưa tồn tại rồi ghi là đã hoàn thành.

12. TRÌNH TỰ VÀ TIÊU CHÍ BÀN GIAO

Thực hiện tuần tự: rà soát và lập checklist -> sửa database/MySQL -> phân quyền/kiến trúc/workflow -> frontend đủ màn hình -> tệp/thông báo/đánh giá -> quản trị/báo cáo -> seed -> test -> tài liệu và chạy demo.

Hoàn thành khi:
- Cài từ hướng dẫn trên database sạch riêng và chạy được; giữ an toàn dữ liệu cũ.
- 4 vai trò có menu/chức năng đúng và bị chặn đúng ở server.
- Luồng end-to-end chạy được bằng UI, dữ liệu lưu thật, reload vẫn còn; các nhánh rút/từ chối/chuyển/xử lý lại/đánh giá hoạt động.
- Upload/download private, quản trị và báo cáo hoạt động; không có nút chết hoặc số liệu nghiệp vụ hard-code.
- Seed có thống kê chứng minh, test bắt buộc đã chạy, không có lỗi nghiêm trọng còn che giấu.
- Tài liệu khớp mã nguồn; tôi có thể đọc để hiểu và trình bày đồ án.
- Bàn giao URL local đang được kiểm tra tại thời điểm bàn giao, tài khoản, lệnh bật/tắt server, báo cáo thay đổi và kết quả test. Không tuyên bố sẵn sàng production khi chưa có bằng chứng.

Nếu môi trường chặn MySQL, dependency hoặc công cụ test, ghi đúng trở ngại, tiếp tục các phần độc lập và nêu rõ phần chưa nghiệm thu; không lấy SQLite hoặc mock thay bằng chứng MySQL.

Hãy bắt đầu bằng kiểm tra hiện trạng, trình bày ngắn checklist còn thiếu rồi trực tiếp triển khai và kiểm chứng đến khi bàn giao.
```

# Đặc tả yêu cầu và use case

## Phạm vi

Một trường, nhiều đơn vị; hệ thống tiếp nhận FEEDBACK (phản ánh) và PROPOSAL (kiến nghị). Mỗi hồ sơ có một cán bộ/đơn vị hiện phụ trách. MVP không có đăng ký công khai, SSO, email giao dịch, AI, diễn đàn, hồ sơ ẩn danh hay mở lại CLOSED.

## Yêu cầu chức năng

| Mã | Chức năng và điều kiện nghiệm thu |
|---|---|
| FR01 | Đăng nhập/logout/me bằng session, khóa tài khoản, giới hạn 5 lần/phút theo email+IP |
| FR02 | Quản trị tài khoản, gán vai trò, đơn vị, thành viên, danh mục; không xóa lịch sử tham chiếu |
| FR03 | Tạo nháp UUID duy nhất theo sinh viên, lưu/sửa, chỉ chủ thấy |
| FR04 | Gửi từ nội dung đã lưu; mã duy nhất, SLA chốt, retry không trùng |
| FR05 | Danh sách/chi tiết có phân trang/tìm/lọc/sắp xếp whitelist, đúng quyền |
| FR06 | Phân loại, phân công, chuyển có lý do, từ chối có lý do |
| FR07 | Cán bộ được giao bắt đầu, yêu cầu bổ sung, trao đổi, trả lời |
| FR08 | Sinh viên bổ sung, rút trước phân công, đóng/xử lý lại với lý do |
| FR09 | Ghi chú/tệp nội bộ không rò qua hồ sơ, file, lịch sử, thông báo |
| FR10 | Tệp PDF/JPG/PNG, 5 MB, 3 tệp/nội dung, private download |
| FR11 | Đánh giá 1–5, chính chủ CLOSED, một lần |
| FR12 | Thông báo sự kiện quan trọng, đọc/chưa đọc, kiểm tra lại quyền hiện tại |
| FR13 | Báo cáo theo ngày gửi/loại/trạng thái/danh mục/đơn vị, CSV, scope thống nhất |
| FR14 | Seed 100 hồ sơ có đường đi hợp lệ, tệp thật, đánh giá và thống kê đối chiếu |

## Quy tắc nghiệp vụ

DRAFT → SUBMITTED → ASSIGNED → IN_PROGRESS → RESOLVED → CLOSED là đường chính. Nhánh: SUBMITTED → REJECTED/CANCELLED; IN_PROGRESS → WAITING_STUDENT → IN_PROGRESS; RESOLVED → IN_PROGRESS khi yêu cầu xử lý lại. Chuyển phân công ở ASSIGNED/IN_PROGRESS luôn về ASSIGNED và ghi lịch sử kể cả ASSIGNED → ASSIGNED.

Sau khi gửi, không sửa title/description/type gốc. Điều phối có use case classify riêng đổi category_id, ghi audit và giữ due_at. Không tự đóng; không mở lại CLOSED. Lịch sử/assignment/audit chỉ ghi thêm trong API thông thường.

Mọi cập nhật hồ sơ phải có version, transaction và lock. Version cũ 409; dữ liệu không hợp lệ 422, chưa đăng nhập 401, thiếu quyền 403, ID/endpoint không tồn tại 404. Submit retry dùng submitted_version nhận diện đúng lần gửi; UUID dùng cho retry tạo nháp.

## Use case chính

| UC | Tác nhân | Luồng chính | Ngoại lệ |
|---|---|---|---|
| UC01 Đăng nhập | Mọi tài khoản | Nhập email/password → xác thực → regenerate session → me | Sai/khóa: 401; quá thử: 429; CSRF thiếu: 419 |
| UC02 Lưu nháp | STUDENT | UUID → tạo → nhập nội dung → PUT version → thêm tệp | UUID trùng trả hồ sơ cũ; người khác 403; version cũ 409 |
| UC03 Gửi | Chủ nháp | Lưu nội dung → submit version → mã/SLA/history/notify | Thiếu nội dung/danh mục ngừng 422; retry hợp lệ trả hồ sơ hiện tại |
| UC04 Tiếp nhận | COORDINATOR | Mở SUBMITTED → phân loại → chọn thành viên STAFF hợp lệ → assign | Nháp 403; người nhận không hợp lệ 422; từ chối cần lý do |
| UC05 Chuyển | COORDINATOR | ASSIGNED/IN_PROGRESS → chọn người khác + lý do → ASSIGNED | Trạng thái khác 409; thiếu lý do 422; cán bộ cũ mất quyền |
| UC06 Xử lý | STAFF hiện tại | start → trao đổi/nội bộ → request-information/resolve | Sai người/khóa/sai vai trò 403; sai trạng thái 409 |
| UC07 Bổ sung | Chủ hồ sơ | WAITING_STUDENT → gửi body → INFO_RESPONSE + IN_PROGRESS | Không cho giả kind/visibility; lỗi history rollback cả message |
| UC08 Hoàn tất | Chủ hồ sơ | Xem RESOLVED → close → rating | Chưa CLOSED 409, không chính chủ 403, đánh giá lại 409 |
| UC09 Xử lý lại | Chủ hồ sơ | RESOLVED + reason → IN_PROGRESS, resolved_at NULL | CLOSED không mở; thiếu lý do 422; giữ due_at/kết quả cũ |
| UC10 Tệp | Người có quyền | Upload trong nháp/tin do mình tạo → policy download | Sai MIME/nội dung/số lượng/kích thước 422; ngoài quyền 403 |
| UC11 Quản trị | ADMIN | CRUD thêm/sửa/ngừng, gán roles/membership | Trùng 422; không tự gỡ ADMIN; cần chuyển hồ sơ trước ngừng cán bộ |
| UC12 Báo cáo | Vai trò nghiệp vụ | Lọc → query đã scope → group/AVG/overdue → CSV | ADMIN thuần 403; không tính DRAFT, không tính hồ sơ kết thúc là quá hạn |

## Yêu cầu phi chức năng và giới hạn đo

- Dữ liệu lưu UTC; hiển thị Việt Nam. DB chính MySQL 8.4/InnoDB/utf8mb4, FK/UNIQUE/CHECK bật.
- Session HttpOnly, CSRF, SameSite Lax, cùng origin, không token localStorage; môi trường HTTP local không bật Secure cookie.
- API không trả password, cookie, storage_key hoặc ghi chú nội bộ ngoài quyền.
- Responsive và bàn phím: focus rõ, nhãn form, dialog native giữ focus, menu mobile, bảng cuộn trong vùng bảng.
- Kiểm thử MySQL, TypeScript, build và browser đều là các kiểm tra riêng.
- Mục tiêu p95 list/detail <2 giây ở 50 người/10.000 hồ sơ CHƯA đo. Không báo đó là kết quả.
- Chưa kiểm thử Docker, production HTTPS, đa máy, khôi phục thảm họa đầy đủ hoặc tải diện rộng. Không có trình quét mã độc; tệp được kiểm loại/nội dung cơ bản, giới hạn và download riêng tư.

# Hướng dẫn sử dụng

Mở http://127.0.0.1:8000 sau khi chạy run.bat. Màn hình “Tài khoản” cho biết email và các vai trò hiện tại. Menu thay theo vai trò; máy nhỏ bấm Menu để mở điều hướng.

## Sinh viên

1. Đăng nhập, mở Yêu cầu của tôi. Dùng tìm mã/tiêu đề, lọc trạng thái/loại/danh mục và phân trang.
2. Tạo hồ sơ: chọn Phản ánh hoặc Kiến nghị, nhập tiêu đề, nội dung và danh mục. Lưu nháp để quay lại sửa. Nháp chỉ mình bạn xem được.
3. Trong nháp, tải tối đa 3 minh chứng PDF/JPG/PNG, 5 MB/tệp. Có thể xóa file gốc khi còn nháp.
4. Bấm Gửi hồ sơ. Nội dung được lưu trước, sau đó server kiểm tra và chốt mã/hạn xử lý. Sau gửi không sửa nội dung gốc; trao đổi/bổ sung trong khung riêng.
5. Trước khi được phân công có thể Rút hồ sơ, xác nhận trong dialog. Sau phân công nút này không còn hợp lệ.
6. Khi Chờ bổ sung, nhập nội dung và Gửi trao đổi; hồ sơ về Đang xử lý. Có thể thêm tối đa 3 tệp vào tin nhắn do mình vừa tạo.
7. Khi Đã trả lời, đọc kết quả. Nếu còn lỗi, Yêu cầu xử lý lại và tự nhập lý do. Nếu đã đạt, Xác nhận đóng. Hồ sơ đã đóng không mở lại.
8. Sau đóng, bấm Đánh giá 1–5 và nhận xét tùy chọn; chỉ gửi một lần.
9. Xem tiến độ, thông báo, báo cáo của riêng mình. Ghi chú và file nội bộ không được gửi đến tài khoản sinh viên.

## Điều phối

1. Hàng đợi tiếp nhận chỉ hiển thị hồ sơ đã gửi. Có tìm kiếm, lọc và phân trang thực.
2. Mở hồ sơ SUBMITTED, kiểm tra nội dung/danh mục. Phân loại lại nếu cần; hành động giữ nguyên hạn và ghi audit.
3. Phân công: chọn đơn vị hoạt động và cán bộ STAFF đang là thành viên hoạt động của đơn vị. Không thể giao cho sinh viên, quản trị thuần hay tài khoản khóa.
4. Nếu ngoài phạm vi, bấm Từ chối và nhập lý do bắt buộc; sinh viên thấy lý do này.
5. Với ASSIGNED/IN_PROGRESS, Chuyển xử lý sang người khác, nhập lý do. Hồ sơ trở lại ASSIGNED; cán bộ mới cần bắt đầu. Cán bộ cũ mất quyền hồ sơ/tệp/thông báo liên quan.
6. Có thể trao đổi, ghi chú nội bộ, xem lịch sử phân công và báo cáo toàn bộ hồ sơ đã gửi trong trường.
7. Điều phối không thay cán bộ bấm bắt đầu hoặc gửi trả lời chính thức nếu chưa có vai trò STAFF và đang được giao.

## Cán bộ

1. Công việc được giao chỉ có hồ sơ hiện thuộc quyền. Không dựa trên việc “đã từng được giao”.
2. ASSIGNED: bấm Bắt đầu xử lý.
3. IN_PROGRESS: gửi trao đổi, ghi chú nội bộ, yêu cầu bổ sung có nội dung hoặc trả lời chính thức.
4. Khi WAITING_STUDENT, chờ chủ hồ sơ gửi bổ sung. Trao đổi của cán bộ không tự giả thành INFO_RESPONSE.
5. Trả lời chính thức là kết quả được sinh viên đọc và xác nhận. Sau yêu cầu xử lý lại, đọc lý do và kết quả trước đó, tiếp tục xử lý với cùng hạn cũ.
6. Tệp của tin nhắn chỉ tác giả có quyền hiện tại được bổ sung; file ghi chú nội bộ theo quyền nội bộ.

## Quản trị

Các tab: Tài khoản, Vai trò, Đơn vị, Thành viên đơn vị, Danh mục. Thêm/sửa qua form, có lỗi theo trường. Không xóa dữ liệu nền đã tham chiếu; dùng “Đang hoạt động” để bật/tắt.

- Tài khoản: họ tên, email, mã sinh viên tùy chọn, mật khẩu ít nhất 12 ký tự, một hoặc nhiều vai trò. Khi sửa, để trống mật khẩu để giữ nguyên.
- Vai trò: bốn code nghiệp vụ cố định; có thể sửa tên hiển thị trong danh sách quản trị và gán quyền cho tài khoản.
- Thành viên: tạo cán bộ với vai trò STAFF trước, chọn đơn vị và cán bộ hoặc nhập ID. Cặp thành viên cũ giữ nguyên, chỉ sửa trạng thái.
- Danh mục: chọn đơn vị mặc định, số ngày xử lý 1–365. Đổi SLA chỉ áp dụng hồ sơ gửi sau đó.
- Không tự khóa/gỡ ADMIN của chính mình. Phải chuyển/hoàn tất hồ sơ còn được giao trước khi ngừng cán bộ/thành viên/đơn vị.
- Muốn điều phối phải được gán thêm COORDINATOR và tải lại phiên/tài khoản. ADMIN thuần không có menu tạo hồ sơ hay đọc phản ánh.

## Thông báo và báo cáo

Thông báo của mình có dấu chưa đọc; bấm Mở hồ sơ hoặc Đánh dấu đã đọc. Thông báo gắn hồ sơ không còn quyền sẽ bị loại khi tải lại.

Báo cáo lọc theo ngày gửi (giờ Việt Nam), loại, trạng thái, danh mục, đơn vị. Tổng không gồm DRAFT; quá hạn chỉ SUBMITTED/ASSIGNED/IN_PROGRESS/WAITING_STUDENT có now > due_at; điểm trung bình chỉ những hồ sơ có đánh giá. Có xuất CSV theo cùng bộ lọc/quyền.

## Khi thao tác lỗi

- 422: sửa các trường được nêu trong khung lỗi; dữ liệu nhập vẫn giữ.
- 409: người khác hoặc tab khác vừa cập nhật. Tải lại phiên bản rồi đối chiếu trước khi gửi lại; không tự động ghi đè.
- 401/419: form đăng nhập xuất hiện để khôi phục phiên. Đăng nhập lại cùng tài khoản sẽ giữ form/nội dung đang nhập; đổi sang tài khoản khác sẽ tạo không gian mới.
- 403: tài khoản không có quyền hoặc vừa bị chuyển hồ sơ/ngừng hoạt động.
- Mất mạng: giữ form, thử lại khi có kết nối. Tạo nháp/gửi có cơ chế chống trùng ở server; không bấm liên tục.

Các form giữ dữ liệu trong lúc báo lỗi; nếu chủ động rời trang hoặc đổi tài khoản, nội dung chưa lưu có thể mất. Lưu nháp thường xuyên. Đây là giới hạn được công bố, không có autosave nền trong MVP.

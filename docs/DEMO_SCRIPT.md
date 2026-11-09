# Kịch bản demo 8–10 phút

Chuẩn bị: chạy run.bat, kiểm tra demo:verify trước khi thao tác; mở ba trình duyệt/profile để cookie không lẫn. Mật khẩu demo Password123!. Dùng staff4 cho ví dụ CNTT, staff1 cho CTSV. Khi chạy lại không reset dữ liệu đã demo; có thể dùng bản cài sạch riêng.

| Thời gian | Thao tác | Điều cần giải thích |
|---|---|---|
| 0:00–0:45 | Trang đăng nhập, giới thiệu bốn vai trò | Một trường nhiều đơn vị, dữ liệu giả lập và phân quyền server |
| 0:45–2:00 | student1 tạo “Không đăng nhập cổng học tập”, chọn Tài khoản sinh viên; lưu nháp, thêm PNG/PDF, gửi | UUID chống trùng; draft riêng tư; gửi lấy nội dung đã lưu, sinh mã và chốt hạn |
| 2:00–3:00 | coordinator tìm mã vừa tạo, phân công CNTT → Cán bộ Hoàng Anh | Chọn thành viên hợp lệ; transaction/version/FK ghép |
| 3:00–4:15 | staff4 bắt đầu, ghi nội bộ, yêu cầu bổ sung | Quyền start khác quyền xem; message và status cùng transaction |
| 4:15–5:00 | student1 tải lại, không thấy ghi chú nội bộ, gửi bổ sung | Server bỏ dữ liệu nội bộ khỏi JSON; WAITING_STUDENT → IN_PROGRESS |
| 5:00–6:00 | staff4 trả lời; student1 yêu cầu xử lý lại với lý do | RESOLVED khác CLOSED; xóa resolved_at hiện tại, giữ kết quả/hạn cũ |
| 6:00–7:00 | staff4 trả lời lần hai; student1 đóng, đánh giá 4 sao | Không tự đóng, không mở CLOSED, đánh giá một lần |
| 7:00–8:00 | Báo cáo và dashboard; lọc/CSV, thông báo | Không DRAFT; quá hạn chỉ hồ sơ mở; trung bình chỉ hồ sơ được đánh giá |
| 8:00–9:00 | ADMIN thuần quản trị; thử URL hồ sơ; coordinator chuyển hồ sơ demo | ADMIN không mặc nhiên đọc; cán bộ cũ mất quyền sau chuyển |
| 9:00–10:00 | Mở TEST_REPORT, sơ đồ sequence và FAQ | MySQL thực, test cạnh tranh/rollback; giới hạn Docker/tải lớn chưa đo |

Nhánh dự phòng: DEMO-0061 đang RESOLVED cho student1 để đóng nhanh; DEMO-0033 để xem lịch sử xử lý lại và JPEG nội bộ; DEMO-0047 cho student7 để bổ sung; DEMO-0083 cho student3 để đánh giá hồ sơ đã đóng.

Không hứa “24 giờ phản hồi” hoặc “production ready”. Nêu SLA 7 ngày lịch của danh mục demo và kết quả test đã chạy. Sau demo, số lượng/trạng thái/đánh giá thay đổi là đúng; thống kê 92/10/3,75 là snapshot dataset sạch.

# Ma trận quyền

Tài khoản phải hoạt động. Nhiều vai trò được tính hợp các quyền rõ ràng theo từng use case. ADMIN không có cơ chế before() cho phép mọi thứ. Quyền đọc và quyền xử lý là hai kiểm tra khác nhau.

| Use case | STUDENT | COORDINATOR | STAFF | ADMIN thuần |
|---|---|---|---|---|
| Tạo nháp | Có | Không | Không | Không |
| Đọc nháp/sửa nháp/tệp nháp | Chính chủ | Không | Không | Không |
| Đọc hồ sơ đã gửi | Chính chủ | Tất cả đã gửi trong trường | Hiện được giao, thành viên/đơn vị hoạt động | Không |
| Gửi/rút | Chính chủ, DRAFT/SUBMITTED tương ứng | Không | Không | Không |
| Phân loại/phân công/chuyển/từ chối | Không | Theo trạng thái hợp lệ | Không | Không |
| Bắt đầu/yêu cầu bổ sung/trả lời chính thức | Không | Không | Hiện được giao, STAFF hợp lệ | Không |
| Trao đổi công khai | Chính chủ, hồ sơ còn nhận trao đổi | Hồ sơ đã gửi còn nhận trao đổi | Hiện được giao, hồ sơ còn nhận trao đổi | Không |
| Ghi chú/tệp nội bộ | Không | Có | Hiện được giao | Không |
| Đóng/xử lý lại | Chính chủ, RESOLVED | Không | Không | Không |
| Đánh giá | Chính chủ, CLOSED, một lần | Không | Không | Không |
| Báo cáo/CSV | Hồ sơ của mình | Hồ sơ đã gửi | Hồ sơ hiện được giao | Không |
| Thông báo | Chỉ của mình và còn quyền hồ sơ | Tương tự | Mất quyền sau chuyển thì thông báo bị lọc | Thông báo không gắn hồ sơ nếu có |
| Quản trị dữ liệu nền | Không | Không | Không | Có |

Nguồn thực thi: TicketPolicy, Access::scope/owner/staff/canSeeInternal; ActiveAccount; FormRequest quản trị; AttachmentService::canDownload; NotificationController::query; ReportingService nhận query đã scope.

Sinh viên thấy các bước tiến độ hợp lệ (DRAFT, SUBMITTED, ASSIGNED, IN_PROGRESS...) và lý do từ chối/xử lý lại của mình; lý do phân công/chuyển do điều phối nhập được giữ nội bộ. Danh sách và chi tiết dùng TicketResource, không gửi ghi chú nội bộ ra trình duyệt.

Tài khoản ADMIN+COORDINATOR có cả quyền quản trị và điều phối. STUDENT+STAFF thấy hợp của hồ sơ mình và hồ sơ hiện được giao; start vẫn cần đúng nhánh STAFF, không được dựa vào quyền chủ sở hữu.

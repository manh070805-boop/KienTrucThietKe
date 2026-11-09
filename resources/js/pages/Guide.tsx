import { PageHead, roleLabels } from "../components/UI";
import type { User } from "../types";
export function Guide({
    user,
    account = false,
}: {
    user: User;
    account?: boolean;
}) {
    return (
        <>
            <PageHead
                title={account ? "Tài khoản của tôi" : "Hướng dẫn sử dụng"}
            />
            <section className="card prose">
                {account ? (
                    <>
                        <h2>{user.full_name}</h2>
                        <p>Email: {user.email}</p>
                        <p>Mã sinh viên: {user.student_code || "—"}</p>
                        <p>
                            Vai trò:{" "}
                            {user.roles.map((r) => roleLabels[r]).join(", ")}
                        </p>
                        <p>
                            Liên hệ quản trị khi cần cập nhật thông tin hoặc mật
                            khẩu.
                        </p>
                    </>
                ) : (
                    <>
                        <h2>Từ ý kiến đến kết quả</h2>
                        <p>
                            <strong>Sinh viên:</strong> Tạo hồ sơ → lưu nháp →
                            thêm minh chứng → gửi. Theo dõi trao đổi và bổ sung
                            khi được yêu cầu. Sau khi cán bộ trả lời, xác nhận
                            đóng hoặc yêu cầu xử lý lại kèm lý do. Hồ sơ đã đóng
                            được đánh giá một lần.
                        </p>
                        <p>
                            <strong>Điều phối:</strong> Vào hàng đợi tiếp nhận,
                            kiểm tra danh mục và phân công cho cán bộ thuộc đơn
                            vị. Có thể từ chối hồ sơ vừa gửi với lý do hoặc
                            chuyển hồ sơ đã phân công/đang xử lý sang người
                            khác.
                        </p>
                        <p>
                            <strong>Cán bộ:</strong> Vào công việc được giao,
                            bắt đầu xử lý, yêu cầu sinh viên bổ sung hoặc gửi
                            trả lời chính thức. Ghi chú nội bộ chỉ cán bộ phụ
                            trách và điều phối xem được.
                        </p>
                        <p>
                            <strong>Quản trị:</strong> Quản lý tài khoản, vai
                            trò, đơn vị, thành viên và danh mục. Muốn điều phối
                            cần được gán thêm quyền điều phối.
                        </p>
                        <h2>Khi có thông báo lỗi</h2>
                        <p>
                            Lỗi 409 nghĩa là hồ sơ vừa được cập nhật ở nơi khác.
                            Tải lại phiên bản, đối chiếu tiến độ rồi thử lại;
                            nội dung đang nhập trong form được giữ. Lỗi 403
                            nghĩa là tài khoản hiện tại chưa được phép thao tác.
                        </p>
                        <p>
                            Tệp cho phép: PDF, JPG, PNG; tối đa 5 MB mỗi tệp và
                            3 tệp mỗi nội dung/tin nhắn. Hồ sơ đã gửi giữ nguyên
                            nội dung gốc và hạn xử lý. Hồ sơ đã đóng không mở
                            lại.
                        </p>
                    </>
                )}
            </section>
        </>
    );
}

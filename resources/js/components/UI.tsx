import React from "react";
import { ApiError, errorText } from "../api";
import { Link } from "../router";
import type { Ticket } from "../types";
export const statusLabels: Record<string, string> = {
    DRAFT: "Bản nháp",
    SUBMITTED: "Đã gửi",
    ASSIGNED: "Đã phân công",
    IN_PROGRESS: "Đang xử lý",
    WAITING_STUDENT: "Chờ bổ sung",
    RESOLVED: "Đã trả lời",
    CLOSED: "Đã đóng",
    REJECTED: "Từ chối",
    CANCELLED: "Đã rút",
};
export const roleLabels: Record<string, string> = {
    STUDENT: "Sinh viên",
    STAFF: "Cán bộ",
    COORDINATOR: "Điều phối",
    ADMIN: "Quản trị",
};
export function date(value?: string | null): string {
    return value
        ? new Intl.DateTimeFormat("vi-VN", {
              dateStyle: "short",
              timeStyle: "short",
              timeZone: "Asia/Ho_Chi_Minh",
          }).format(new Date(value))
        : "—";
}
export function Badge({ status }: { status: string }) {
    return (
        <span className={"badge " + status.toLowerCase()}>
            {statusLabels[status] || status}
        </span>
    );
}
export function ErrorBox({ error }: { error: unknown }) {
    if (!error) return null;
    return (
        <div className="alert error" role="alert">
            <strong>
                {error instanceof ApiError && error.status
                    ? error.status + " · "
                    : ""}
                {errorText(error)}
            </strong>
            {error instanceof ApiError &&
                Object.entries(error.errors).map(([field, messages]) => (
                    <p key={field} data-field={field}>
                        {(
                            {
                                title: "Tiêu đề",
                                description: "Nội dung",
                                category_id: "Danh mục",
                                body: "Nội dung trao đổi",
                                reason: "Lý do",
                                file: "Tệp",
                                staff_id: "Cán bộ",
                                password: "Mật khẩu",
                                roles: "Vai trò",
                                email: "Email",
                                version: "Phiên bản",
                                is_active: "Trạng thái",
                            } as Record<string, string>
                        )[field] || field}
                        : {messages.join(" ")}
                    </p>
                ))}
        </div>
    );
}
export function Loading() {
    return (
        <div className="empty" role="status">
            <span className="spinner" />
            Đang tải dữ liệu…
        </div>
    );
}
export function Empty({
    text = "Chưa có dữ liệu phù hợp.",
}: {
    text?: string;
}) {
    return <div className="empty">{text}</div>;
}
export function PageHead({
    title,
    subtitle,
    children,
}: {
    title: string;
    subtitle?: string;
    children?: React.ReactNode;
}) {
    return (
        <div className="page-head">
            <div>
                <p className="eyebrow">STUDENT VOICE / KHÔNG GIAN LÀM VIỆC</p>
                <h1>{title}</h1>
                {subtitle && <p className="muted">{subtitle}</p>}
            </div>
            <div className="actions">{children}</div>
        </div>
    );
}
export function Pager({
    meta,
    change,
}: {
    meta: { current_page: number; last_page: number; total: number };
    change: (page: number) => void;
}) {
    return (
        <div className="pager">
            <span>
                {meta.total} kết quả · Trang {meta.current_page}/
                {meta.last_page}
            </span>
            <div className="actions">
                <button
                    className="secondary"
                    disabled={meta.current_page <= 1}
                    onClick={() => change(meta.current_page - 1)}
                >
                    ← Trước
                </button>
                <button
                    className="secondary"
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => change(meta.current_page + 1)}
                >
                    Sau →
                </button>
            </div>
        </div>
    );
}
export function TicketTable({ tickets }: { tickets: Ticket[] }) {
    if (!tickets.length) return <Empty />;
    return (
        <div className="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Hồ sơ</th>
                        <th>Trạng thái</th>
                        <th>Người phụ trách</th>
                        <th>Hạn xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    {tickets.map((t) => (
                        <tr key={t.id}>
                            <td>
                                <Link
                                    className="ticket-title"
                                    to={"/tickets/" + t.id}
                                >
                                    {t.title || "Bản nháp chưa đặt tên"}
                                </Link>
                                <small>
                                    {t.code || "NHÁP #" + t.id} ·{" "}
                                    {t.type === "FEEDBACK"
                                        ? "Phản ánh"
                                        : "Kiến nghị"}{" "}
                                    · {t.category?.name || "Chưa phân loại"}
                                </small>
                            </td>
                            <td>
                                <Badge status={t.status} />
                            </td>
                            <td>
                                {t.assigned_staff?.full_name || "Chờ phân công"}
                                <small>{t.assigned_department?.name}</small>
                            </td>
                            <td className={t.is_overdue ? "overdue" : ""}>
                                {date(t.due_at)}
                                {t.is_overdue && <small>Đang quá hạn</small>}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

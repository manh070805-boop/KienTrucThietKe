import React, { useState } from "react";
import { api } from "../api";
import { useData } from "../hooks/useData";
import { Attachments } from "../components/Attachments";
import { ActionDialog, actionLabels } from "../components/ActionDialog";
import {
    Badge,
    ErrorBox,
    Loading,
    PageHead,
    date,
    statusLabels,
} from "../components/UI";
import { DraftEditor } from "./DraftEditor";
import type { Directory, Ticket, User } from "../types";
export function TicketDetail({
    id,
    user,
    directory,
}: {
    id: number;
    user: User;
    directory: Directory;
}) {
    const { data, error, loading, reload } = useData<{ data: Ticket }>(
        "/tickets/" + id,
    );
    const [action, setAction] = useState<string | null>(null);
    const [body, setBody] = useState("");
    const [messageError, setMessageError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    const [success, setSuccess] = useState("");
    const ticket = data?.data;
    async function message(e: React.FormEvent) {
        e.preventDefault();
        if (!ticket) return;
        setBusy(true);
        setMessageError(null);
        try {
            await api("/tickets/" + id + "/messages", "POST", {
                body,
                version: ticket.version,
            });
            setBody("");
            setSuccess("Đã gửi trao đổi.");
            reload();
        } catch (e) {
            setMessageError(e);
        } finally {
            setBusy(false);
        }
    }
    if (!ticket && loading) return <Loading />;
    if (error)
        return (
            <>
                <PageHead title="Chi tiết hồ sơ" />
                <ErrorBox error={error} />
                <button className="secondary" onClick={reload}>
                    Thử lại
                </button>
            </>
        );
    if (!ticket) return null;
    if (ticket.status === "DRAFT" && ticket.actions.includes("update"))
        return (
            <DraftEditor
                user={user}
                ticket={ticket}
                directory={directory}
                reload={reload}
                refreshing={loading}
            />
        );
    return (
        <>
            <PageHead
                title={ticket.code || "Hồ sơ #" + id}
                subtitle={
                    "Cập nhật " +
                    date(ticket.updated_at) +
                    " · Phiên bản " +
                    ticket.version
                }
            >
                <button className="secondary" onClick={reload}>
                    ↻ Tải lại hồ sơ
                </button>
            </PageHead>
            {success && (
                <div className="alert success" role="status">
                    {success}
                </div>
            )}
            <div className="detail-grid">
                <div>
                    <section className="card original">
                        <div className="section-head">
                            <Badge status={ticket.status} />
                            <span className="muted">
                                {ticket.type === "FEEDBACK"
                                    ? "Phản ánh"
                                    : "Kiến nghị"}
                            </span>
                        </div>
                        <h2 className="detail-title">{ticket.title}</h2>
                        <p className="note">
                            NỘI DUNG GỐC · {ticket.student.full_name} ·{" "}
                            {ticket.student.student_code}
                        </p>
                        <p className="prose">{ticket.description}</p>
                        <Attachments
                            files={ticket.attachments || []}
                            ticketId={id}
                            version={ticket.version}
                            done={reload}
                        />
                    </section>
                    <section className="card">
                        <div className="section-head">
                            <h2>Trao đổi & kết quả</h2>
                            {ticket.actions.includes("internal-note") && (
                                <button
                                    aria-label="Ghi chú nội bộ"
                                    className="secondary"
                                    disabled={loading}
                                    onClick={() => setAction("internal-note")}
                                >
                                    ＋ Ghi chú nội bộ
                                </button>
                            )}
                        </div>
                        {ticket.messages?.length === 0 && (
                            <p className="muted">Chưa có trao đổi.</p>
                        )}
                        {ticket.messages?.map((m) => (
                            <article
                                className={
                                    "message " +
                                    (m.visibility === "STAFF_ONLY"
                                        ? "internal"
                                        : "")
                                }
                                key={m.id}
                            >
                                <div className="message-meta">
                                    <strong>{m.author}</strong>
                                    <time>{date(m.created_at)}</time>
                                </div>
                                <span className="message-kind">
                                    {m.visibility === "STAFF_ONLY"
                                        ? "🔒 Nội bộ"
                                        : (
                                              {
                                                  INFO_REQUEST:
                                                      "Yêu cầu bổ sung",
                                                  INFO_RESPONSE:
                                                      "Sinh viên bổ sung",
                                                  RESOLUTION:
                                                      "Trả lời chính thức",
                                                  COMMENT: "Trao đổi",
                                              } as Record<string, string>
                                          )[m.kind]}
                                </span>
                                <p className="prose">{m.body}</p>
                                <Attachments
                                    files={m.attachments}
                                    ticketId={id}
                                    messageId={m.id}
                                    version={ticket.version}
                                    editable={m.can_attach && !loading}
                                    done={reload}
                                />
                            </article>
                        ))}
                        {ticket.actions.includes("messages") && (
                            <form onSubmit={message} className="message-form">
                                <label>
                                    {ticket.status === "WAITING_STUDENT" &&
                                    ticket.student_id === user.id
                                        ? "Nội dung bổ sung"
                                        : "Nội dung trao đổi"}
                                    <textarea
                                        aria-label={
                                            ticket.status ===
                                                "WAITING_STUDENT" &&
                                            ticket.student_id === user.id
                                                ? "Nội dung bổ sung"
                                                : "Nội dung trao đổi"
                                        }
                                        required
                                        rows={4}
                                        maxLength={10000}
                                        value={body}
                                        onChange={(e) =>
                                            setBody(e.target.value)
                                        }
                                        placeholder="Nhập nội dung của bạn…"
                                    />
                                </label>
                                <ErrorBox error={messageError} />
                                <div className="actions">
                                    <button
                                        className="primary"
                                        disabled={busy || loading}
                                    >
                                        {busy ? "Đang gửi…" : "Gửi trao đổi"}
                                    </button>
                                    {!!messageError && (
                                        <button
                                            type="button"
                                            className="secondary"
                                            onClick={reload}
                                        >
                                            Tải lại phiên bản, giữ nội dung
                                        </button>
                                    )}
                                </div>
                            </form>
                        )}
                    </section>
                </div>
                <aside>
                    <section className="card">
                        <h2>Thông tin xử lý</h2>
                        <dl>
                            <dt>Danh mục</dt>
                            <dd>{ticket.category?.name}</dd>
                            <dt>Đơn vị</dt>
                            <dd>
                                {ticket.assigned_department?.name ||
                                    "Chờ tiếp nhận"}
                            </dd>
                            <dt>Cán bộ phụ trách</dt>
                            <dd>
                                {ticket.assigned_staff?.full_name ||
                                    "Chưa phân công"}
                            </dd>
                            <dt>Ngày gửi</dt>
                            <dd>{date(ticket.submitted_at)}</dd>
                            <dt>Hạn xử lý</dt>
                            <dd className={ticket.is_overdue ? "overdue" : ""}>
                                {date(ticket.due_at)}
                                {ticket.is_overdue && " · Đang quá hạn"}
                            </dd>
                        </dl>
                        <div className="stack-actions">
                            {ticket.actions
                                .filter(
                                    (a) =>
                                        ![
                                            "messages",
                                            "internal-note",
                                            "update",
                                        ].includes(a),
                                )
                                .map((a) => (
                                    <button
                                        disabled={loading}
                                        key={a}
                                        className={
                                            ["reject", "cancel"].includes(a)
                                                ? "secondary danger"
                                                : [
                                                        "assign",
                                                        "resolve",
                                                        "close",
                                                    ].includes(a)
                                                  ? "primary"
                                                  : "secondary"
                                        }
                                        onClick={() => setAction(a)}
                                    >
                                        {actionLabels[a]}
                                    </button>
                                ))}
                        </div>
                    </section>
                    {ticket.rating && (
                        <section className="card soft">
                            <h2>Đánh giá từ sinh viên</h2>
                            <div className="stars">
                                {"★".repeat(ticket.rating.score)}
                                {"☆".repeat(5 - ticket.rating.score)}
                            </div>
                            <p>
                                {ticket.rating.comment || "Không có nhận xét."}
                            </p>
                            <small>{date(ticket.rating.created_at)}</small>
                        </section>
                    )}
                    <section className="card">
                        <h2>Tiến độ hồ sơ</h2>
                        <ol className="timeline">
                            {ticket.history?.map((h, i) => (
                                <li key={i}>
                                    <strong>{statusLabels[h.to]}</strong>
                                    <small>{date(h.changed_at)}</small>
                                    {h.reason && <p>{h.reason}</p>}
                                </li>
                            ))}
                        </ol>
                    </section>
                    {ticket.can_internal && !!ticket.assignments?.length && (
                        <section className="card">
                            <h2>Lịch sử phân công</h2>
                            {ticket.assignments.map((a, i) => (
                                <div key={i} className="assignment">
                                    <strong>{a.staff}</strong>
                                    <small>
                                        {a.department} · {date(a.created_at)}
                                    </small>
                                    <p>{a.reason}</p>
                                </div>
                            ))}
                        </section>
                    )}
                </aside>
            </div>
            {action && (
                <ActionDialog
                    action={action}
                    ticket={ticket}
                    directory={directory}
                    close={() => setAction(null)}
                    refresh={reload}
                    done={() => {
                        setSuccess("Đã cập nhật hồ sơ.");
                        reload();
                    }}
                />
            )}
        </>
    );
}

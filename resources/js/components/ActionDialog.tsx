import React, { useEffect, useRef, useState } from "react";
import { api } from "../api";
import { ErrorBox } from "./UI";
import { useDialog } from "../hooks/useDialog";
import type { Directory, Ticket } from "../types";
export const actionLabels: Record<string, string> = {
    submit: "Gửi hồ sơ",
    assign: "Phân công",
    transfer: "Chuyển xử lý",
    classify: "Phân loại lại",
    start: "Bắt đầu xử lý",
    "request-information": "Yêu cầu bổ sung",
    resolve: "Trả lời chính thức",
    reopen: "Yêu cầu xử lý lại",
    close: "Xác nhận đóng",
    reject: "Từ chối",
    cancel: "Rút hồ sơ",
    rate: "Đánh giá",
    "internal-note": "Ghi chú nội bộ",
};
export function ActionDialog({
    action,
    ticket,
    directory,
    close,
    done,
    refresh,
}: {
    action: string;
    ticket: Ticket;
    directory: Directory;
    close: () => void;
    done: () => void;
    refresh: () => void;
}) {
    const ref = useRef<HTMLDialogElement>(null);
    const [error, setError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    const [body, setBody] = useState("");
    const [reason, setReason] = useState("");
    const [department, setDepartment] = useState(
        String(
            ticket.assigned_department?.id ||
                directory.categories.find((c) => c.id === ticket.category_id)
                    ?.default_department_id ||
                directory.departments[0]?.id ||
                "",
        ),
    );
    const [staff, setStaff] = useState("");
    const [category, setCategory] = useState(String(ticket.category_id || ""));
    const [score, setScore] = useState("5");
    const [comment, setComment] = useState("");
    useDialog(ref);
    const assignment = ["assign", "transfer"].includes(action);
    const needsReason = ["transfer", "reject", "reopen"].includes(action);
    const needsBody = [
        "request-information",
        "resolve",
        "internal-note",
    ].includes(action);
    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            await api("/tickets/" + ticket.id + "/" + action, "POST", {
                version: ticket.version,
                ...(assignment
                    ? {
                          department_id: Number(department),
                          staff_id: Number(staff),
                          reason: reason || null,
                      }
                    : {}),
                ...(needsReason ? { reason } : {}),
                ...(needsBody ? { body } : {}),
                ...(action === "classify"
                    ? { category_id: Number(category) }
                    : {}),
                ...(action === "rate"
                    ? { score: Number(score), comment: comment || null }
                    : {}),
            });
            done();
            close();
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    return (
        <dialog
            ref={ref}
            aria-labelledby="action-title"
            onCancel={(e) => {
                if (busy) e.preventDefault();
                else close();
            }}
        >
            <form onSubmit={submit}>
                <div className="section-head">
                    <h2 id="action-title">{actionLabels[action]}</h2>
                    <button
                        type="button"
                        className="icon-button"
                        aria-label="Đóng hộp thoại"
                        disabled={busy}
                        onClick={close}
                    >
                        ×
                    </button>
                </div>
                <p className="muted">
                    {ticket.code || "Bản nháp"} · {ticket.title}
                </p>
                {assignment && (
                    <>
                        <label>
                            Đơn vị tiếp nhận
                            <select
                                aria-label="Đơn vị tiếp nhận"
                                required
                                value={department}
                                onChange={(e) => {
                                    setDepartment(e.target.value);
                                    setStaff("");
                                }}
                            >
                                {directory.departments.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Cán bộ phụ trách
                            <select
                                aria-label="Cán bộ phụ trách"
                                required
                                value={staff}
                                onChange={(e) => setStaff(e.target.value)}
                            >
                                <option value="">Chọn cán bộ…</option>
                                {directory.staff
                                    .filter(
                                        (s) =>
                                            String(s.department_id) ===
                                            department,
                                    )
                                    .map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.full_name}
                                        </option>
                                    ))}
                            </select>
                        </label>
                    </>
                )}
                {(needsReason || assignment) && (
                    <label>
                        Lý do{needsReason ? " (bắt buộc)" : " (không bắt buộc)"}
                        <textarea
                            required={needsReason}
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            maxLength={500}
                            rows={3}
                        />
                    </label>
                )}
                {needsBody && (
                    <label>
                        {action === "internal-note"
                            ? "Nội dung chỉ dành cho cán bộ/điều phối"
                            : "Nội dung gửi sinh viên"}
                        <textarea
                            required
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            maxLength={10000}
                            rows={6}
                        />
                    </label>
                )}
                {action === "classify" && (
                    <>
                        <label>
                            Danh mục
                            <select
                                aria-label="Danh mục"
                                required
                                value={category}
                                onChange={(e) => setCategory(e.target.value)}
                            >
                                {directory.categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <p className="note">
                            Phân loại lại được ghi nhật ký và giữ nguyên hạn xử
                            lý.
                        </p>
                    </>
                )}
                {action === "rate" && (
                    <>
                        <label>
                            Mức hài lòng
                            <select
                                aria-label="Mức hài lòng"
                                value={score}
                                onChange={(e) => setScore(e.target.value)}
                            >
                                {[5, 4, 3, 2, 1].map((n) => (
                                    <option key={n} value={n}>
                                        {n} / 5 sao
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Nhận xét (không bắt buộc)
                            <textarea
                                maxLength={1000}
                                value={comment}
                                onChange={(e) => setComment(e.target.value)}
                            />
                        </label>
                        <p className="note">Mỗi hồ sơ chỉ đánh giá một lần.</p>
                    </>
                )}
                {["cancel", "close", "start", "submit"].includes(action) && (
                    <p>
                        Bạn xác nhận thực hiện thao tác “{actionLabels[action]}
                        ”?
                        {action === "close" &&
                            " Hồ sơ đã đóng sẽ không được mở lại trong phiên bản này."}
                    </p>
                )}
                <ErrorBox error={error} />
                <div className="dialog-actions">
                    <button
                        className="secondary"
                        type="button"
                        disabled={busy}
                        onClick={close}
                    >
                        Quay lại
                    </button>
                    <button className="primary" disabled={busy}>
                        {busy ? "Đang lưu…" : "Xác nhận"}
                    </button>
                </div>
                {!!error && (
                    <button
                        type="button"
                        className="text-button"
                        disabled={busy}
                        onClick={refresh}
                    >
                        ↻ Tải lại phiên bản hồ sơ (giữ nội dung đang nhập)
                    </button>
                )}
            </form>
        </dialog>
    );
}

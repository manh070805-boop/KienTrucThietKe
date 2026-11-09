import React, { useState } from "react";
import { api } from "../api";
import { navigate } from "../router";
import { ErrorBox, PageHead } from "../components/UI";
import { Attachments } from "../components/Attachments";
import type { Directory, Ticket, User } from "../types";
export function DraftEditor({
    user,
    ticket,
    directory,
    reload,
    refreshing = false,
}: {
    user: User;
    ticket?: Ticket;
    directory: Directory;
    reload?: () => void;
    refreshing?: boolean;
}) {
    const [type, setType] = useState(ticket?.type || "FEEDBACK");
    const [title, setTitle] = useState(ticket?.title || "");
    const [description, setDescription] = useState(ticket?.description || "");
    const [category, setCategory] = useState(String(ticket?.category_id || ""));
    const [error, setError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    const [success, setSuccess] = useState("");
    const [created, setCreated] = useState<Ticket | null>(null);
    async function refreshVersion() {
        if (ticket) {
            reload?.();
            return;
        }
        if (!created) return;
        setBusy(true);
        try {
            const current = (
                await api<{ data: Ticket }>("/tickets/" + created.id)
            ).data;
            setCreated(current);
            if (current.status !== "DRAFT") navigate("/tickets/" + current.id);
            setError(null);
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    async function save(submit: boolean) {
        setBusy(true);
        setError(null);
        setSuccess("");
        try {
            let current = ticket || created;
            if (!current) {
                const key = "draft-request-" + user.id;
                let uuid = sessionStorage.getItem(key);
                if (!uuid) {
                    uuid = crypto.randomUUID();
                    sessionStorage.setItem(key, uuid);
                }
                current = (
                    await api<{ data: Ticket }>("/tickets", "POST", {
                        client_request_id: uuid,
                        type,
                    })
                ).data;
                setCreated(current);
            }
            const saved = await api<{ data: Ticket }>(
                "/tickets/" + current.id,
                "PUT",
                {
                    version: current.version,
                    type,
                    title: title || null,
                    description: description || null,
                    category_id: category ? Number(category) : null,
                },
            );
            setCreated(saved.data);
            current = saved.data;
            if (submit)
                current = (
                    await api<{ data: Ticket }>(
                        "/tickets/" + current.id + "/submit",
                        "POST",
                        { version: current.version },
                    )
                ).data;
            sessionStorage.removeItem("draft-request-" + user.id);
            if (!ticket || submit) {
                reload?.();
                navigate("/tickets/" + current.id);
            } else {
                setSuccess("Đã lưu bản nháp.");
                reload?.();
            }
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    return (
        <>
            <PageHead
                title={ticket ? "Chỉnh sửa bản nháp" : "Tạo hồ sơ mới"}
                subtitle="Lưu nháp để bổ sung minh chứng. Nội dung gốc được giữ nguyên sau khi gửi."
            />
            <div className="detail-grid">
                <section className="card">
                    <form
                        onSubmit={(e: React.FormEvent) => {
                            e.preventDefault();
                            save(false);
                        }}
                    >
                        <label>
                            Loại hồ sơ
                            <select
                                value={type}
                                onChange={(e) => setType(e.target.value)}
                            >
                                <option value="FEEDBACK">Phản ánh</option>
                                <option value="PROPOSAL">Kiến nghị</option>
                            </select>
                        </label>
                        <label>
                            Tiêu đề
                            <input
                                value={title}
                                maxLength={255}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder="Bạn muốn nhà trường hỗ trợ điều gì?"
                            />
                        </label>
                        <label>
                            Danh mục
                            <select
                                aria-label="Danh mục"
                                value={category}
                                onChange={(e) => setCategory(e.target.value)}
                            >
                                <option value="">Chọn danh mục…</option>
                                {directory.categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Nội dung gốc
                            <textarea
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                rows={9}
                                maxLength={10000}
                                placeholder="Mô tả sự việc, thời gian, địa điểm và mong muốn của bạn…"
                            />
                        </label>
                        <ErrorBox error={error} />
                        {success && (
                            <div className="alert success" role="status">
                                {success}
                            </div>
                        )}
                        <div className="actions">
                            <button
                                className="secondary"
                                disabled={busy || refreshing}
                            >
                                {busy ? "Đang lưu…" : "Lưu nháp"}
                            </button>
                            <button
                                type="button"
                                className="primary"
                                disabled={busy || refreshing}
                                onClick={() => save(true)}
                            >
                                Gửi hồ sơ ↗
                            </button>
                            {(ticket || created) && (
                                <button
                                    type="button"
                                    className="text-button"
                                    disabled={busy || refreshing}
                                    onClick={refreshVersion}
                                >
                                    ↻ Tải lại phiên bản
                                </button>
                            )}
                        </div>
                    </form>
                </section>
                <aside>
                    <section className="card">
                        <h2>Minh chứng</h2>
                        {ticket ? (
                            <Attachments
                                files={ticket.attachments || []}
                                ticketId={ticket.id}
                                version={ticket.version}
                                editable={!refreshing}
                                done={() => reload?.()}
                            />
                        ) : (
                            <p className="muted">
                                Lưu bản nháp trước để tải lên tối đa 3 tệp
                                PDF/JPG/PNG.
                            </p>
                        )}
                    </section>
                    <section className="card soft">
                        <h2>Trước khi gửi</h2>
                        <p>
                            Kiểm tra thông tin chính xác và chọn danh mục phù
                            hợp. Hạn xử lý được tính theo danh mục tại thời điểm
                            gửi.
                        </p>
                        <p>
                            Nhà trường chỉ nhận hồ sơ khi bạn bấm “Gửi hồ sơ”.
                            Bản nháp chỉ bạn có quyền xem.
                        </p>
                    </section>
                </aside>
            </div>
        </>
    );
}

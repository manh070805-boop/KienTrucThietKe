import React, { useState } from "react";
import { api } from "../api";
import { ErrorBox } from "./UI";
import type { Attachment } from "../types";
export function Attachments({
    files,
    ticketId,
    version,
    messageId,
    editable,
    done,
}: {
    files: Attachment[];
    ticketId: number;
    version: number;
    messageId?: number;
    editable?: boolean;
    done: () => void;
}) {
    const [error, setError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    const [selected, setSelected] = useState<File | null>(null);
    async function upload(e: React.FormEvent) {
        e.preventDefault();
        if (!selected) return;
        setBusy(true);
        setError(null);
        const form = new FormData();
        form.set("file", selected);
        form.set("version", String(version));
        if (messageId) form.set("message_id", String(messageId));
        try {
            await api("/tickets/" + ticketId + "/attachments", "POST", form);
            setSelected(null);
            done();
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    async function remove(id: number) {
        setBusy(true);
        setError(null);
        try {
            await api("/attachments/" + id, "DELETE", { version });
            done();
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    return (
        <div className="attachments">
            {files.map((f) => (
                <div className="file" key={f.id}>
                    <span aria-hidden="true">▧</span>
                    <a href={f.url}>
                        {f.original_name}
                        <small>
                            {Math.ceil(f.size_bytes / 1024)} KB · Tải xuống
                        </small>
                    </a>
                    {editable && !messageId && (
                        <button
                            type="button"
                            className="text-button danger"
                            disabled={busy}
                            onClick={() => remove(f.id)}
                        >
                            Xóa
                        </button>
                    )}
                </div>
            ))}
            {editable && files.length < 3 && (
                <form className="file-upload" onSubmit={upload}>
                    <label>
                        Thêm tệp (PDF/JPG/PNG · tối đa 5 MB)
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) =>
                                setSelected(e.target.files?.[0] || null)
                            }
                        />
                    </label>
                    <button className="secondary" disabled={busy || !selected}>
                        {busy ? "Đang tải…" : "Tải tệp lên"}
                    </button>
                </form>
            )}
            <ErrorBox error={error} />
        </div>
    );
}

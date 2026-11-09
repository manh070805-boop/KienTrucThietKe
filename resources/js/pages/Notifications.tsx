import { useState } from "react";
import { api } from "../api";
import { useData } from "../hooks/useData";
import { Link } from "../router";
import {
    date,
    Empty,
    ErrorBox,
    Loading,
    PageHead,
    Pager,
} from "../components/UI";
import type { Notice, Pagination } from "../types";
export function Notifications() {
    const [page, setPage] = useState(1);
    const [error, setError] = useState<unknown>(null);
    const data = useData<Pagination<Notice>>("/notifications?page=" + page);
    async function read(id: number) {
        try {
            await api("/notifications/" + id + "/read", "POST");
            data.reload();
        } catch (e) {
            setError(e);
        }
    }
    return (
        <>
            <PageHead
                title="Thông báo"
                subtitle={
                    (data.data?.meta.unread ?? "…") +
                    " thông báo chưa đọc trong phạm vi quyền hiện tại"
                }
            />
            <section className="card">
                <ErrorBox error={error || data.error} />
                {data.loading ? (
                    <Loading />
                ) : (
                    data.data && (
                        <>
                            {!data.data.data.length && <Empty />}
                            {data.data.data.map((n) => (
                                <article
                                    className={
                                        "notification " +
                                        (!n.read_at ? "unread" : "")
                                    }
                                    key={n.id}
                                >
                                    <div>
                                        <strong>{n.title}</strong>
                                        <p>{n.body}</p>
                                        <small>
                                            {date(n.created_at)} ·{" "}
                                            {n.read_at ? "Đã đọc" : "Chưa đọc"}
                                        </small>
                                    </div>
                                    <div className="actions">
                                        {n.ticket_id && (
                                            <Link
                                                to={"/tickets/" + n.ticket_id}
                                                className="secondary"
                                            >
                                                Mở hồ sơ ↗
                                            </Link>
                                        )}
                                        {!n.read_at && (
                                            <button
                                                className="text-button"
                                                onClick={() => read(n.id)}
                                            >
                                                Đánh dấu đã đọc
                                            </button>
                                        )}
                                    </div>
                                </article>
                            ))}
                            <Pager meta={data.data.meta} change={setPage} />
                        </>
                    )
                )}
            </section>
        </>
    );
}

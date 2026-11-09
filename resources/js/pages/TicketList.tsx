import React, { useState } from "react";
import { queryString } from "../api";
import { Link } from "../router";
import { useData } from "../hooks/useData";
import {
    ErrorBox,
    Loading,
    PageHead,
    Pager,
    TicketTable,
    statusLabels,
} from "../components/UI";
import type { Directory, Pagination, Ticket, User } from "../types";
export function TicketList({
    user,
    directory,
    view,
}: {
    user: User;
    directory: Directory;
    view: "mine" | "queue" | "assigned";
}) {
    const initial = new URLSearchParams(location.search);
    const [filters, setFilters] = useState({
        search: initial.get("search") || "",
        status: initial.get("status") || "",
        type: initial.get("type") || "",
        category_id: initial.get("category_id") || "",
        sort: initial.get("sort") || "updated_at",
        direction: initial.get("direction") || "desc",
        page: Number(initial.get("page")) || 1,
    });
    const [search, setSearch] = useState(filters.search);
    const url = "/tickets?" + queryString({ ...filters, view, per_page: 15 });
    const { data, error, loading, reload } = useData<Pagination<Ticket>>(url);
    function change(changes: Partial<typeof filters>) {
        const next = { ...filters, page: 1, ...changes };
        setFilters(next);
        history.replaceState(
            {},
            "",
            location.pathname + "?" + queryString(next),
        );
    }
    return (
        <>
            <PageHead
                title={
                    {
                        mine: "Yêu cầu của tôi",
                        queue: "Hàng đợi tiếp nhận",
                        assigned: "Công việc được giao",
                    }[view]
                }
                subtitle="Tìm kiếm, lọc và theo dõi hồ sơ được lưu trong hệ thống."
            >
                {user.roles.includes("STUDENT") && (
                    <Link to="/tickets/new" className="primary">
                        ＋ Tạo hồ sơ
                    </Link>
                )}
            </PageHead>
            <section className="card">
                <form
                    className="filters"
                    onSubmit={(e: React.FormEvent) => {
                        e.preventDefault();
                        change({ search });
                    }}
                >
                    <label className="search-field">
                        Tìm hồ sơ
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Nhập mã hoặc tiêu đề…"
                        />
                    </label>
                    <button className="secondary">Tìm kiếm</button>
                    <label>
                        Trạng thái
                        <select
                            aria-label="Trạng thái"
                            value={filters.status}
                            onChange={(e) => change({ status: e.target.value })}
                        >
                            <option value="">Tất cả trạng thái</option>
                            {Object.entries(statusLabels)
                                .filter(
                                    ([s]) => view === "mine" || s !== "DRAFT",
                                )
                                .map(([s, l]) => (
                                    <option key={s} value={s}>
                                        {l}
                                    </option>
                                ))}
                        </select>
                    </label>
                    <label>
                        Loại
                        <select
                            aria-label="Loại"
                            value={filters.type}
                            onChange={(e) => change({ type: e.target.value })}
                        >
                            <option value="">Tất cả loại</option>
                            <option value="FEEDBACK">Phản ánh</option>
                            <option value="PROPOSAL">Kiến nghị</option>
                        </select>
                    </label>
                    <label>
                        Danh mục
                        <select
                            aria-label="Danh mục"
                            value={filters.category_id}
                            onChange={(e) =>
                                change({ category_id: e.target.value })
                            }
                        >
                            <option value="">Tất cả danh mục</option>
                            {directory.categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label>
                        Sắp xếp
                        <select
                            value={filters.sort}
                            onChange={(e) => change({ sort: e.target.value })}
                        >
                            <option value="updated_at">Cập nhật</option>
                            <option value="created_at">Ngày tạo</option>
                            <option value="due_at">Hạn xử lý</option>
                            <option value="status">Trạng thái</option>
                        </select>
                    </label>
                    <label>
                        Chiều
                        <select
                            value={filters.direction}
                            onChange={(e) =>
                                change({ direction: e.target.value })
                            }
                        >
                            <option value="desc">Giảm dần</option>
                            <option value="asc">Tăng dần</option>
                        </select>
                    </label>
                </form>
                <ErrorBox error={error} />
                <div className="section-head">
                    <p className="muted">
                        {data?.meta.total ?? "…"} hồ sơ phù hợp
                    </p>
                    <button className="text-button" onClick={reload}>
                        ↻ Tải lại
                    </button>
                </div>
                {loading ? (
                    <Loading />
                ) : (
                    data && (
                        <>
                            <TicketTable tickets={data.data} />
                            <Pager
                                meta={data.meta}
                                change={(page) => change({ page })}
                            />
                        </>
                    )
                )}
            </section>
        </>
    );
}

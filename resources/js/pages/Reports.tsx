import { useState } from "react";
import { queryString } from "../api";
import { useData } from "../hooks/useData";
import { Metrics } from "./Dashboard";
import {
    Empty,
    ErrorBox,
    Loading,
    PageHead,
    statusLabels,
} from "../components/UI";
import type { Directory, Report } from "../types";
export function Reports({ directory }: { directory: Directory }) {
    const [filters, setFilters] = useState({
        from: "",
        to: "",
        type: "",
        status: "",
        category_id: "",
        department_id: "",
    });
    const query = queryString(filters);
    const { data, error, loading } = useData<{ data: Report }>(
        "/reports?" + query,
    );
    const names = (key: string, id: string) =>
        key === "by_status"
            ? statusLabels[id]
            : key === "by_type"
              ? id === "FEEDBACK"
                  ? "Phản ánh"
                  : "Kiến nghị"
              : key === "by_category"
                ? directory.categories.find((c) => String(c.id) === id)?.name ||
                  "Danh mục #" + id
                : directory.departments.find((d) => String(d.id) === id)
                      ?.name || (id ? "Đơn vị #" + id : "Chưa phân công");
    return (
        <>
            <PageHead
                title="Báo cáo & thống kê"
                subtitle="Chỉ tính hồ sơ đã gửi, trong phạm vi quyền của tài khoản."
            >
                <a className="secondary" href={"/api/reports/export?" + query}>
                    ↓ Xuất CSV
                </a>
            </PageHead>
            <section className="card">
                <div className="filters">
                    <label>
                        Từ ngày gửi
                        <input
                            type="date"
                            value={filters.from}
                            onChange={(e) =>
                                setFilters({ ...filters, from: e.target.value })
                            }
                        />
                    </label>
                    <label>
                        Đến ngày gửi
                        <input
                            type="date"
                            value={filters.to}
                            onChange={(e) =>
                                setFilters({ ...filters, to: e.target.value })
                            }
                        />
                    </label>
                    <label>
                        Loại
                        <select
                            value={filters.type}
                            onChange={(e) =>
                                setFilters({ ...filters, type: e.target.value })
                            }
                        >
                            <option value="">Tất cả</option>
                            <option value="FEEDBACK">Phản ánh</option>
                            <option value="PROPOSAL">Kiến nghị</option>
                        </select>
                    </label>
                    <label>
                        Trạng thái
                        <select
                            value={filters.status}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    status: e.target.value,
                                })
                            }
                        >
                            <option value="">Tất cả</option>
                            {Object.entries(statusLabels)
                                .filter(([s]) => s !== "DRAFT")
                                .map(([s, n]) => (
                                    <option key={s} value={s}>
                                        {n}
                                    </option>
                                ))}
                        </select>
                    </label>
                    <label>
                        Danh mục
                        <select
                            value={filters.category_id}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    category_id: e.target.value,
                                })
                            }
                        >
                            <option value="">Tất cả</option>
                            {directory.categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label>
                        Đơn vị
                        <select
                            value={filters.department_id}
                            onChange={(e) =>
                                setFilters({
                                    ...filters,
                                    department_id: e.target.value,
                                })
                            }
                        >
                            <option value="">Tất cả</option>
                            {directory.departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
            </section>
            <ErrorBox error={error} />
            {loading ? (
                <Loading />
            ) : (
                data && (
                    <>
                        <Metrics report={data.data} />
                        <div className="report-grid">
                            {[
                                "by_status",
                                "by_type",
                                "by_category",
                                "by_department",
                            ].map((key) => (
                                <section className="card" key={key}>
                                    <h2>
                                        {
                                            {
                                                by_status: "Theo trạng thái",
                                                by_type: "Theo loại",
                                                by_category: "Theo danh mục",
                                                by_department: "Theo đơn vị",
                                            }[key]
                                        }
                                    </h2>
                                    <div className="status-breakdown">
                                        {Object.entries(
                                            data.data[key as "by_status"],
                                        ).map(([id, n]) => (
                                            <div key={id}>
                                                <span>{names(key, id)}</span>
                                                <strong>{n}</strong>
                                                <progress
                                                    value={n}
                                                    max={
                                                        data.data
                                                            .total_submitted ||
                                                        1
                                                    }
                                                />
                                            </div>
                                        ))}
                                    </div>
                                    {!data.data.total_submitted && <Empty />}
                                </section>
                            ))}
                        </div>
                    </>
                )
            )}
        </>
    );
}

import { useData } from "../hooks/useData";
import { Link } from "../router";
import {
    Empty,
    ErrorBox,
    Loading,
    PageHead,
    TicketTable,
    statusLabels,
} from "../components/UI";
import type { Pagination, Report, Ticket, User } from "../types";
export function Metrics({ report }: { report: Report }) {
    return (
        <div className="metrics">
            <article className="metric">
                <span>
                    Hồ sơ đã gửi <b>↗</b>
                </span>
                <strong>{report.total_submitted}</strong>
                <small>Trong phạm vi quyền và bộ lọc</small>
            </article>
            <article className="metric peach">
                <span>
                    Đang mở quá hạn <b>◷</b>
                </span>
                <strong>{report.overdue_open}</strong>
                <small>Giữ nguyên hạn từ lần gửi</small>
            </article>
            <article className="metric lavender">
                <span>
                    Đã hoàn tất <b>✓</b>
                </span>
                <strong>{report.by_status.CLOSED || 0}</strong>
                <small>Sinh viên đã xác nhận đóng</small>
            </article>
            <article className="metric yellow">
                <span>
                    Điểm hài lòng <b>☆</b>
                </span>
                <strong>
                    {report.rating.average?.toLocaleString("vi-VN") ?? "—"}
                    <em>/5</em>
                </strong>
                <small>{report.rating.total} lượt đánh giá</small>
            </article>
        </div>
    );
}
export function Dashboard({ user }: { user: User }) {
    const report = useData<{ data: Report }>("/dashboard");
    const list = useData<Pagination<Ticket>>(
        "/tickets?per_page=5&sort=updated_at",
    );
    const canTickets = user.roles.some((r) =>
        ["STUDENT", "STAFF", "COORDINATOR"].includes(r),
    );
    return (
        <>
            <PageHead
                title={"Xin chào, " + user.full_name.split(" ").pop()}
                subtitle="Cùng lắng nghe. Cùng giải quyết. Cùng tiến bộ."
            >
                {user.roles.includes("STUDENT") && (
                    <Link className="primary" to="/tickets/new">
                        ＋ Tạo hồ sơ
                    </Link>
                )}
            </PageHead>
            <section className="welcome">
                <div>
                    <p className="eyebrow">MỖI GÓP Ý, MỘT BƯỚC TIẾN</p>
                    <h2>
                        Ý kiến được lắng nghe.
                        <br />
                        Tiến độ được nhìn thấy.
                    </h2>
                    <p>Theo dõi hồ sơ và trao đổi với đúng người phụ trách.</p>
                </div>
                <span aria-hidden="true">✳</span>
            </section>
            <ErrorBox error={report.error} />
            {report.loading ? (
                <Loading />
            ) : (
                report.data && (
                    <>
                        {report.data.data.administration && (
                            <section className="card">
                                <h2>Quản trị hệ thống</h2>
                                <div className="admin-counts">
                                    {Object.entries(
                                        report.data.data.administration,
                                    ).map(([k, v]) => (
                                        <Link key={k} to={"/admin/" + k}>
                                            <strong>{v}</strong>
                                            {
                                                (
                                                    {
                                                        users: "Tài khoản",
                                                        departments: "Đơn vị",
                                                        members: "Thành viên",
                                                        categories: "Danh mục",
                                                        roles: "Vai trò",
                                                    } as Record<string, string>
                                                )[k]
                                            }
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        )}
                        {canTickets && (
                            <>
                                <Metrics report={report.data.data} />
                                <div className="dashboard-grid">
                                    <section className="card">
                                        <div className="section-head">
                                            <h2>Cập nhật gần đây</h2>
                                            <Link
                                                to={
                                                    user.roles.includes(
                                                        "COORDINATOR",
                                                    )
                                                        ? "/queue"
                                                        : user.roles.includes(
                                                                "STUDENT",
                                                            )
                                                          ? "/tickets"
                                                          : "/assigned"
                                                }
                                            >
                                                Xem tất cả ↗
                                            </Link>
                                        </div>
                                        <ErrorBox error={list.error} />
                                        {list.loading ? (
                                            <Loading />
                                        ) : (
                                            <TicketTable
                                                tickets={list.data?.data || []}
                                            />
                                        )}
                                    </section>
                                    <section className="card">
                                        <h2>Tiến độ trong không gian</h2>
                                        <p className="muted">
                                            Các hồ sơ đã gửi
                                        </p>
                                        <div className="status-breakdown">
                                            {Object.entries(
                                                report.data.data.by_status,
                                            ).map(([s, n]) => (
                                                <div key={s}>
                                                    <span>
                                                        {statusLabels[s]}
                                                    </span>
                                                    <strong>{n}</strong>
                                                    <progress
                                                        value={n}
                                                        max={
                                                            report.data!.data
                                                                .total_submitted ||
                                                            1
                                                        }
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                        {!report.data.data.total_submitted && (
                                            <Empty />
                                        )}
                                    </section>
                                </div>
                            </>
                        )}
                    </>
                )
            )}
        </>
    );
}

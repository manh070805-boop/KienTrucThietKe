import React, { useState } from "react";
import { Link } from "../router";
import { roleLabels } from "./UI";
import type { User } from "../types";
export function Layout({
    user,
    path,
    logout,
    children,
}: {
    user: User;
    path: string;
    logout: () => void;
    children: React.ReactNode;
}) {
    const [expanded, setExpanded] = useState(false);
    const items = [{ to: "/", label: "Tổng quan", icon: "◈" }];
    if (user.roles.includes("STUDENT"))
        items.push({ to: "/tickets", label: "Yêu cầu của tôi", icon: "▤" });
    if (user.roles.includes("COORDINATOR"))
        items.push({ to: "/queue", label: "Hàng đợi tiếp nhận", icon: "≋" });
    if (user.roles.includes("STAFF"))
        items.push({
            to: "/assigned",
            label: "Công việc được giao",
            icon: "✓",
        });
    items.push({ to: "/notifications", label: "Thông báo", icon: "◉" });
    if (user.roles.some((r) => ["STUDENT", "STAFF", "COORDINATOR"].includes(r)))
        items.push({ to: "/reports", label: "Báo cáo", icon: "▥" });
    if (user.roles.includes("ADMIN"))
        items.push({
            to: "/admin/users",
            label: "Quản trị hệ thống",
            icon: "⚙",
        });
    items.push({ to: "/guide", label: "Hướng dẫn sử dụng", icon: "?" });
    return (
        <div className="app-shell">
            <a className="skip-link" href="#main">
                Đến nội dung chính
            </a>
            <aside className={"sidebar " + (expanded ? "expanded" : "")}>
                <Link to="/" className="brand">
                    <span className="brand-mark">
                        sv<span>✦</span>
                    </span>
                    <span>
                        student voice<small>LẮNG NGHE ĐỂ THAY ĐỔI</small>
                    </span>
                </Link>
                <button
                    className="menu-toggle secondary"
                    aria-expanded={expanded}
                    onClick={() => setExpanded(!expanded)}
                >
                    ☰ Menu
                </button>
                <nav aria-label="Điều hướng chính">
                    {items.map((item) => (
                        <Link
                            key={item.to}
                            to={item.to}
                            className={
                                path === item.to ||
                                (item.to.startsWith("/admin") &&
                                    path.startsWith("/admin"))
                                    ? "nav-link active"
                                    : "nav-link"
                            }
                            aria-current={path === item.to ? "page" : undefined}
                        >
                            <span aria-hidden="true">{item.icon}</span>
                            {item.label}
                        </Link>
                    ))}
                </nav>
                <div className="sidebar-note">
                    <span className="sparkle">✦</span>
                    <strong>Mỗi góp ý đều có giá trị.</strong>
                    <p>
                        Theo dõi tiến độ, cùng nhà trường xây dựng môi trường
                        học tập tốt hơn.
                    </p>
                </div>
                <div className="profile">
                    <span className="avatar">{user.full_name[0]}</span>
                    <div>
                        <strong>{user.full_name}</strong>
                        <small>
                            {user.roles.map((r) => roleLabels[r]).join(" · ")}
                        </small>
                    </div>
                </div>
            </aside>
            <div className="workspace">
                <header className="topbar">
                    <span>CỔNG PHẢN ÁNH & KIẾN NGHỊ</span>
                    <div>
                        <Link to="/account" className="account-link">
                            Tài khoản
                        </Link>
                        <button className="text-button" onClick={logout}>
                            Đăng xuất ↗
                        </button>
                    </div>
                </header>
                <main id="main" tabIndex={-1}>
                    {children}
                </main>
                <footer>
                    Student Voice · Đồ án Kiến trúc và Thiết kế phần mềm{" "}
                    <span>Thời gian hiển thị: Việt Nam (UTC+7)</span>
                </footer>
            </div>
        </div>
    );
}

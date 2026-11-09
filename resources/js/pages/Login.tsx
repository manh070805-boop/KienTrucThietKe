import React, { useState } from "react";
import { api, csrf } from "../api";
import { ErrorBox } from "../components/UI";
import type { User } from "../types";
export function Login({
    onLogin,
    expired,
}: {
    onLogin: (user: User) => void | Promise<void>;
    expired: boolean;
}) {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [error, setError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            await csrf();
            const r = await api<{ data: User }>("/login", "POST", {
                email,
                password,
            });
            await onLogin(r.data);
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    return (
        <div className="login-shell">
            <section className="login-story">
                <div className="brand">
                    <span className="brand-mark">sv✦</span>
                    <strong>student voice</strong>
                </div>
                <p className="eyebrow">CÙNG NHAU TẠO NÊN THAY ĐỔI</p>
                <h1>
                    Tiếng nói của bạn.
                    <br />
                    <em>
                        Một trường học
                        <br />
                        tốt hơn.
                    </em>
                </h1>
                <p>
                    Một nơi để gửi phản ánh, chia sẻ kiến nghị và theo dõi những
                    thay đổi từ ý kiến của sinh viên.
                </p>
                <div className="art-grid" aria-hidden="true">
                    <span>✦</span>
                    <span>↗</span>
                    <span>✳</span>
                    <span>◒</span>
                </div>
                <small>MINH BẠCH TRONG TỪNG BƯỚC XỬ LÝ</small>
            </section>
            <main className="login-form">
                <p className="eyebrow">CHÀO MỪNG TRỞ LẠI</p>
                <h2>Đăng nhập</h2>
                <p className="muted">
                    Sử dụng tài khoản do nhà trường cung cấp.
                </p>
                {expired && (
                    <div className="alert" role="status">
                        Phiên đã hết hạn. Đăng nhập lại để tiếp tục.
                    </div>
                )}
                <form onSubmit={submit}>
                    <label>
                        Email
                        <input
                            type="email"
                            autoComplete="username"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="ban@truong.edu.vn"
                        />
                    </label>
                    <label>
                        Mật khẩu
                        <input
                            type="password"
                            autoComplete="current-password"
                            required
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                        />
                    </label>
                    <ErrorBox error={error} />
                    <button className="primary wide" disabled={busy}>
                        {busy ? "Đang đăng nhập…" : "Vào không gian của tôi ↗"}
                    </button>
                </form>
                <p className="note">
                    Tài khoản được quản trị cấp. Liên hệ nhà trường khi cần hỗ
                    trợ truy cập.
                </p>
            </main>
        </div>
    );
}

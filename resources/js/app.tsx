import React, { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import { api, csrf } from "./api";
import { useRoute } from "./router";
import { useData } from "./hooks/useData";
import { Layout } from "./components/Layout";
import { ErrorBox, Loading, PageHead } from "./components/UI";
import { Login } from "./pages/Login";
import { Dashboard } from "./pages/Dashboard";
import { TicketList } from "./pages/TicketList";
import { DraftEditor } from "./pages/DraftEditor";
import { TicketDetail } from "./pages/TicketDetail";
import { Notifications } from "./pages/Notifications";
import { Reports } from "./pages/Reports";
import { Admin } from "./pages/Admin";
import { Guide } from "./pages/Guide";
import type { Directory, Entity, User } from "./types";

function App() {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);
    const [expired, setExpired] = useState(false);
    const [error, setError] = useState<unknown>(null);
    useEffect(() => {
        api<{ data: User }>("/me")
            .then((r) => setUser(r.data))
            .catch((e) => {
                if (e.status !== 401) setError(e);
            })
            .finally(() => setLoading(false));
        const expire = () => {
            setExpired(true);
        };
        window.addEventListener("session-expired", expire);
        return () => window.removeEventListener("session-expired", expire);
    }, []);
    async function logout() {
        try {
            await api("/logout", "POST");
            setUser(null);
            setExpired(false);
        } catch (e) {
            setError(e);
        }
    }
    async function login(u: User) {
        await csrf();
        setUser(u);
        setExpired(false);
        setError(null);
        window.dispatchEvent(new Event("session-restored"));
    }
    if (loading) return <Loading />;
    if (!user)
        return (
            <>
                <ErrorBox error={error} />
                <Login onLogin={login} expired={expired} />
            </>
        );
    return (
        <>
            <div hidden={expired}>
                <Authenticated
                    key={user.id}
                    user={user}
                    logout={logout}
                    globalError={error}
                />
            </div>
            {expired && <Login onLogin={login} expired />}
        </>
    );
}
function Authenticated({
    user,
    logout,
    globalError,
}: {
    user: User;
    logout: () => void;
    globalError: unknown;
}) {
    const path = useRoute();
    const dir = useData<{ data: Directory }>("/directory");
    const directory = dir.data?.data;
    const ticketId = path.match(/^\/tickets\/(\d+)$/)?.[1];
    const adminEntity = path.match(
        /^\/admin\/(users|roles|departments|members|categories)$/,
    )?.[1] as Entity | undefined;
    const allowedTickets = user.roles.some((r) =>
        ["STUDENT", "COORDINATOR", "STAFF"].includes(r),
    );
    let page: React.ReactNode;
    if (path === "/") page = <Dashboard user={user} />;
    else if (path === "/notifications") page = <Notifications />;
    else if (path === "/guide" || path === "/account")
        page = <Guide user={user} account={path === "/account"} />;
    else if (!directory)
        page = dir.error ? (
            <>
                <ErrorBox error={dir.error} />
                <button onClick={dir.reload}>Thử lại</button>
            </>
        ) : (
            <Loading />
        );
    else if (path === "/tickets/new" && user.roles.includes("STUDENT"))
        page = <DraftEditor user={user} directory={directory} />;
    else if (ticketId)
        page = (
            <TicketDetail
                id={Number(ticketId)}
                user={user}
                directory={directory}
            />
        );
    else if (
        ["/tickets", "/queue", "/assigned"].includes(path) &&
        allowedTickets
    )
        page = (
            <TicketList
                user={user}
                directory={directory}
                view={
                    path === "/queue"
                        ? "queue"
                        : path === "/assigned"
                          ? "assigned"
                          : "mine"
                }
            />
        );
    else if (path === "/reports" && allowedTickets)
        page = <Reports directory={directory} />;
    else if (adminEntity && user.roles.includes("ADMIN"))
        page = (
            <Admin
                key={adminEntity}
                entity={adminEntity}
                directory={directory}
            />
        );
    else
        page = (
            <>
                <PageHead title="Không thể mở trang" />
                <div className="alert error" role="alert">
                    Trang không tồn tại hoặc bạn không có quyền truy cập.
                </div>
            </>
        );
    return (
        <Layout user={user} path={path} logout={logout}>
            <ErrorBox error={globalError} />
            <div key={path}>{page}</div>
        </Layout>
    );
}
createRoot(document.getElementById("app")!).render(<App />);

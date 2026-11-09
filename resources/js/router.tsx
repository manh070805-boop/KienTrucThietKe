import React, { useEffect, useState } from "react";
export function navigate(path: string) {
    history.pushState({}, "", path);
    window.dispatchEvent(new Event("route"));
    window.scrollTo(0, 0);
}
export function useRoute() {
    const [path, setPath] = useState(location.pathname);
    useEffect(() => {
        const f = () => setPath(location.pathname);
        window.addEventListener("route", f);
        window.addEventListener("popstate", f);
        return () => {
            window.removeEventListener("route", f);
            window.removeEventListener("popstate", f);
        };
    }, []);
    return path;
}
export function Link({
    to,
    children,
    ...props
}: { to: string; children: React.ReactNode } & Omit<
    React.AnchorHTMLAttributes<HTMLAnchorElement>,
    "href"
>) {
    return (
        <a
            href={to}
            {...props}
            onClick={(e) => {
                if (!e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) {
                    e.preventDefault();
                    navigate(to);
                }
            }}
        >
            {children}
        </a>
    );
}

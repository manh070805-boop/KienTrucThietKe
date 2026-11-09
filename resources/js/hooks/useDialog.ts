import { useEffect, type RefObject } from "react";
export function useDialog(ref: RefObject<HTMLDialogElement | null>) {
    useEffect(() => {
        const show = () =>
            requestAnimationFrame(() => {
                if (ref.current && !ref.current.open) ref.current.showModal();
            });
        const hide = () => ref.current?.close();
        show();
        window.addEventListener("session-expired", hide);
        window.addEventListener("session-restored", show);
        return () => {
            window.removeEventListener("session-expired", hide);
            window.removeEventListener("session-restored", show);
        };
    }, [ref]);
}

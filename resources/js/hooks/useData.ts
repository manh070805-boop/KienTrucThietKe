import { useEffect, useState } from "react";
import { api } from "../api";
export function useData<T>(url: string) {
    const [data, setData] = useState<T | null>(null);
    const [error, setError] = useState<unknown>(null);
    const [loading, setLoading] = useState(true);
    const [revision, setRevision] = useState(0);
    useEffect(() => {
        let active = true;
        setLoading(true);
        setError(null);
        api<T>(url)
            .then((r) => {
                if (active) setData(r);
            })
            .catch((e) => {
                if (active) setError(e);
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [url, revision]);
    return {
        data,
        error,
        loading,
        reload: () => {
            setLoading(true);
            setRevision((r) => r + 1);
        },
    };
}

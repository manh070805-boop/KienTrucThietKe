export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}
let csrfToken = "";
export async function csrf(): Promise<void> {
    const r = await fetch("/api/csrf-cookie", {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
    });
    if (!r.ok)
        throw new ApiError(r.status, "Không thể khởi tạo phiên làm việc.");
    csrfToken = (await r.json()).csrf_token;
}
export async function api<T>(
    url: string,
    method = "GET",
    data?: unknown,
): Promise<T> {
    if (method !== "GET" && !csrfToken) await csrf();
    const headers: Record<string, string> = { Accept: "application/json" };
    if (method !== "GET") headers["X-CSRF-TOKEN"] = csrfToken;
    if (data && !(data instanceof FormData))
        headers["Content-Type"] = "application/json";
    let response: Response;
    try {
        response = await fetch("/api" + url, {
            method,
            credentials: "same-origin",
            headers,
            body:
                data instanceof FormData
                    ? data
                    : data
                      ? JSON.stringify(data)
                      : undefined,
        });
    } catch {
        throw new ApiError(
            0,
            "Không thể kết nối. Kiểm tra mạng và thử lại; nội dung đang nhập vẫn được giữ.",
        );
    }
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (response.status === 401 || response.status === 419) {
            csrfToken = "";
            if (!["/me", "/login"].includes(url))
                window.dispatchEvent(new Event("session-expired"));
        }
        throw new ApiError(
            response.status,
            body.message ||
                ({
                    401: "Vui lòng đăng nhập.",
                    403: "Bạn không có quyền truy cập.",
                    409: "Dữ liệu đã thay đổi. Vui lòng tải lại.",
                }[response.status] ??
                    "Không thể hoàn tất thao tác."),
            body.errors,
        );
    }
    return body as T;
}
export function errorText(e: unknown): string {
    return e instanceof Error ? e.message : "Có lỗi xảy ra.";
}
export function queryString(filters: Record<string, string | number>): string {
    return new URLSearchParams(
        Object.entries(filters)
            .filter(([, v]) => String(v) !== "")
            .map(([k, v]) => [k, String(v)]),
    ).toString();
}

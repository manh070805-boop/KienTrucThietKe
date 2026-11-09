import React, { useEffect, useRef, useState } from "react";
import { api } from "../api";
import { useData } from "../hooks/useData";
import { useDialog } from "../hooks/useDialog";
import { Link } from "../router";
import {
    Empty,
    ErrorBox,
    Loading,
    PageHead,
    Pager,
    roleLabels,
} from "../components/UI";
import type { AdminRow, Directory, Entity, Pagination } from "../types";
const entityLabels: Record<Entity, string> = {
    users: "Tài khoản",
    roles: "Vai trò",
    departments: "Đơn vị",
    members: "Thành viên đơn vị",
    categories: "Danh mục",
};
export function Admin({
    entity,
    directory,
}: {
    entity: Entity;
    directory: Directory;
}) {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState("");
    const [filter, setFilter] = useState("");
    const [edit, setEdit] = useState<AdminRow | true | null>(null);
    const [success, setSuccess] = useState("");
    const result = useData<Pagination<AdminRow>>(
        "/admin/" +
            entity +
            "?page=" +
            page +
            "&search=" +
            encodeURIComponent(filter),
    );
    return (
        <>
            <PageHead
                title="Quản trị hệ thống"
                subtitle="Quản lý dữ liệu nền. Ngừng hoạt động để giữ nguyên lịch sử tham chiếu."
            >
                {entity !== "roles" && (
                    <button className="primary" onClick={() => setEdit(true)}>
                        ＋ Thêm {entityLabels[entity].toLowerCase()}
                    </button>
                )}
            </PageHead>
            <nav className="tabs" aria-label="Nhóm quản trị">
                {Object.entries(entityLabels).map(([e, n]) => (
                    <Link
                        className={e === entity ? "active" : ""}
                        key={e}
                        to={"/admin/" + e}
                    >
                        {n}
                    </Link>
                ))}
            </nav>
            {success && (
                <div className="alert success" role="status">
                    {success}
                </div>
            )}
            <section className="card">
                <form
                    className="filters"
                    onSubmit={(e: React.FormEvent) => {
                        e.preventDefault();
                        setFilter(search);
                        setPage(1);
                    }}
                >
                    <label>
                        Tìm kiếm
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={
                                entity === "members" ? "ID thành viên" : "Tên…"
                            }
                        />
                    </label>
                    <button className="secondary">Tìm kiếm</button>
                </form>
                <ErrorBox error={result.error} />
                {result.loading ? (
                    <Loading />
                ) : (
                    result.data && (
                        <>
                            {!result.data.data.length ? (
                                <Empty />
                            ) : (
                                <div className="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>{entityLabels[entity]}</th>
                                                <th>Thông tin</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {result.data.data.map((row) => (
                                                <tr key={row.id}>
                                                    <td>#{row.id}</td>
                                                    <td>
                                                        <strong>
                                                            {row.full_name ||
                                                                row.name}
                                                        </strong>
                                                        <small>
                                                            {row.email ||
                                                                row.code ||
                                                                row.department}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        {row.roles
                                                            ?.map(
                                                                (r) =>
                                                                    roleLabels[
                                                                        r
                                                                    ],
                                                            )
                                                            .join(", ") ||
                                                            row.department ||
                                                            (row.resolution_days
                                                                ? row.resolution_days +
                                                                  " ngày"
                                                                : row.code)}
                                                    </td>
                                                    <td>
                                                        {row.is_active ===
                                                        undefined
                                                            ? "Vai trò cố định"
                                                            : row.is_active
                                                              ? "Đang hoạt động"
                                                              : "Ngừng hoạt động"}
                                                    </td>
                                                    <td>
                                                        <button
                                                            className="secondary compact"
                                                            onClick={() =>
                                                                setEdit(row)
                                                            }
                                                        >
                                                            Chỉnh sửa
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                            <Pager meta={result.data.meta} change={setPage} />
                        </>
                    )
                )}
            </section>
            {edit && (
                <AdminDialog
                    entity={entity}
                    row={edit === true ? null : edit}
                    directory={directory}
                    close={() => setEdit(null)}
                    done={() => {
                        setSuccess("Đã lưu dữ liệu quản trị.");
                        result.reload();
                    }}
                />
            )}
        </>
    );
}
function AdminDialog({
    entity,
    row,
    directory,
    close,
    done,
}: {
    entity: Entity;
    row: AdminRow | null;
    directory: Directory;
    close: () => void;
    done: () => void;
}) {
    const ref = useRef<HTMLDialogElement>(null);
    const [error, setError] = useState<unknown>(null);
    const [busy, setBusy] = useState(false);
    const [values, setValues] = useState<
        Record<string, string | boolean | string[]>
    >({
        full_name: row?.full_name || "",
        email: row?.email || "",
        student_code: row?.student_code || "",
        password: "",
        roles: row?.roles || ["STUDENT"],
        name: row?.name || "",
        code: row?.code || "",
        is_active: row?.is_active ?? true,
        department_id: String(
            row?.department_id || directory.departments[0]?.id || "",
        ),
        user_id: String(row?.user_id || ""),
        default_department_id: String(
            row?.default_department_id || directory.departments[0]?.id || "",
        ),
        resolution_days: String(row?.resolution_days || 7),
    });
    useDialog(ref);
    const text = (key: string) => String(values[key] ?? "");
    const set = (key: string, value: string | boolean | string[]) =>
        setValues((v) => ({ ...v, [key]: value }));
    function input(key: string, label: string, type = "text", required = true) {
        return (
            <label>
                {label}
                <input
                    type={type}
                    required={required}
                    value={text(key)}
                    onChange={(e) => set(key, e.target.value)}
                    autoComplete={
                        type === "password" ? "new-password" : undefined
                    }
                />
            </label>
        );
    }
    async function save(e: React.FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        const fields: Record<Entity, string[]> = {
            users: [
                "full_name",
                "email",
                "student_code",
                "password",
                "is_active",
                "roles",
            ],
            roles: ["name"],
            departments: ["code", "name", "is_active"],
            members: ["department_id", "user_id", "is_active"],
            categories: [
                "code",
                "name",
                "default_department_id",
                "resolution_days",
                "is_active",
            ],
        };
        const payload: Record<string, unknown> = {};
        fields[entity].forEach((k) => {
            payload[k] = [
                "department_id",
                "user_id",
                "default_department_id",
                "resolution_days",
            ].includes(k)
                ? Number(values[k])
                : values[k] === ""
                  ? null
                  : values[k];
        });
        try {
            await api(
                "/admin/" + entity + (row ? "/" + row.id : ""),
                row ? "PUT" : "POST",
                payload,
            );
            done();
            close();
        } catch (e) {
            setError(e);
        } finally {
            setBusy(false);
        }
    }
    return (
        <dialog
            ref={ref}
            onCancel={(e) => {
                if (busy) e.preventDefault();
                else close();
            }}
            aria-labelledby="admin-dialog-title"
        >
            <form onSubmit={save}>
                <div className="section-head">
                    <h2 id="admin-dialog-title">
                        {row ? "Chỉnh sửa" : "Thêm"}{" "}
                        {entityLabels[entity].toLowerCase()}
                    </h2>
                    <button
                        className="icon-button"
                        type="button"
                        disabled={busy}
                        onClick={close}
                        aria-label="Đóng"
                    >
                        ×
                    </button>
                </div>
                {entity === "users" ? (
                    <>
                        {input("full_name", "Họ và tên")}
                        {input("email", "Email", "email")}
                        {input("student_code", "Mã sinh viên", "text", false)}
                        {input(
                            "password",
                            row
                                ? "Mật khẩu mới (để trống nếu giữ nguyên)"
                                : "Mật khẩu (ít nhất 12 ký tự)",
                            "password",
                            !row,
                        )}
                        <fieldset>
                            <legend>Vai trò</legend>
                            {Object.entries(roleLabels).map(([r, n]) => (
                                <label className="check" key={r}>
                                    <input
                                        type="checkbox"
                                        checked={(
                                            values.roles as string[]
                                        ).includes(r)}
                                        onChange={(e) =>
                                            set(
                                                "roles",
                                                e.target.checked
                                                    ? [
                                                          ...(values.roles as string[]),
                                                          r,
                                                      ]
                                                    : (
                                                          values.roles as string[]
                                                      ).filter((v) => v !== r),
                                            )
                                        }
                                    />
                                    {n}
                                </label>
                            ))}
                        </fieldset>
                    </>
                ) : entity === "members" ? (
                    <>
                        <label>
                            Đơn vị
                            <select
                                required
                                disabled={!!row}
                                value={text("department_id")}
                                onChange={(e) => {
                                    set("department_id", e.target.value);
                                    set("user_id", "");
                                }}
                            >
                                {directory.departments.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            Cán bộ
                            <select
                                disabled={!!row}
                                value={text("user_id")}
                                onChange={(e) => set("user_id", e.target.value)}
                            >
                                <option value="">Chọn cán bộ…</option>
                                {directory.staff
                                    .filter(
                                        (s, i, a) =>
                                            a.findIndex(
                                                (u) => u.id === s.id,
                                            ) === i,
                                    )
                                    .map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.full_name} (#{s.id})
                                        </option>
                                    ))}
                                {row &&
                                    !directory.staff.some(
                                        (s) => s.id === row.user_id,
                                    ) && (
                                        <option value={row.user_id}>
                                            {row.full_name}
                                        </option>
                                    )}
                            </select>
                        </label>
                        <p className="note">
                            Tạo tài khoản với vai trò cán bộ trước khi thêm
                            thành viên. Cặp đơn vị–cán bộ đã lưu được giữ
                            nguyên.
                        </p>
                        {!row &&
                            input(
                                "user_id",
                                "ID cán bộ (có thể chọn ở trên)",
                                "number",
                                true,
                            )}
                    </>
                ) : (
                    <>
                        {entity !== "roles" && input("code", "Mã")}
                        {input("name", "Tên")}
                        {entity === "categories" && (
                            <>
                                <label>
                                    Đơn vị mặc định
                                    <select
                                        required
                                        value={text("default_department_id")}
                                        onChange={(e) =>
                                            set(
                                                "default_department_id",
                                                e.target.value,
                                            )
                                        }
                                    >
                                        {directory.departments.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                {input(
                                    "resolution_days",
                                    "Số ngày xử lý",
                                    "number",
                                )}
                            </>
                        )}
                    </>
                )}
                {entity !== "roles" && (
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={!!values.is_active}
                            onChange={(e) => set("is_active", e.target.checked)}
                        />
                        Đang hoạt động
                    </label>
                )}
                <ErrorBox error={error} />
                <div className="dialog-actions">
                    <button
                        type="button"
                        className="secondary"
                        disabled={busy}
                        onClick={close}
                    >
                        Hủy
                    </button>
                    <button className="primary" disabled={busy}>
                        {busy ? "Đang lưu…" : "Lưu thay đổi"}
                    </button>
                </div>
            </form>
        </dialog>
    );
}

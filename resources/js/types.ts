export type User = {
    id: number;
    full_name: string;
    email: string;
    student_code: string | null;
    roles: string[];
};
export type Category = {
    id: number;
    code: string;
    name: string;
    default_department_id: number;
    resolution_days: number;
};
export type Department = { id: number; code: string; name: string };
export type Directory = {
    categories: Category[];
    departments: Department[];
    staff: { id: number; department_id: number; full_name: string }[];
};
export type Attachment = {
    id: number;
    original_name: string;
    mime_type: string;
    size_bytes: number;
    url: string;
};
export type Message = {
    id: number;
    kind: string;
    visibility: string;
    body: string;
    author: string;
    created_at: string;
    attachments: Attachment[];
    can_attach: boolean;
};
export type Ticket = {
    id: number;
    student_id: number;
    code: string | null;
    type: string;
    status: string;
    title: string | null;
    description: string | null;
    category_id: number | null;
    version: number;
    created_at: string;
    updated_at: string;
    submitted_at: string | null;
    due_at: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    is_overdue: boolean;
    can_internal: boolean;
    actions: string[];
    student: { id: number; full_name: string; student_code: string | null };
    category: Pick<Category, "id" | "code" | "name"> | null;
    assigned_department: Department | null;
    assigned_staff: { id: number; full_name: string } | null;
    rating: {
        score: number;
        comment: string | null;
        created_at: string;
    } | null;
    attachments?: Attachment[];
    messages?: Message[];
    history?: {
        from: string | null;
        to: string;
        reason: string | null;
        changed_at: string;
    }[];
    assignments?: {
        department: string;
        staff: string;
        reason: string | null;
        created_at: string;
    }[];
};
export type Report = {
    total_submitted: number;
    by_status: Record<string, number>;
    by_type: Record<string, number>;
    by_category: Record<string, number>;
    by_department: Record<string, number>;
    overdue_open: number;
    rating: { average: number | null; total: number };
    administration?: Record<string, number>;
};
export type Pagination<T> = {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        unread?: number;
    };
};
export type Notice = {
    id: number;
    ticket_id: number | null;
    title: string;
    body: string;
    read_at: string | null;
    created_at: string;
};
export type Entity =
    "users" | "roles" | "departments" | "members" | "categories";
export type AdminRow = {
    id: number;
    full_name?: string;
    email?: string;
    student_code?: string | null;
    name?: string;
    code?: string;
    is_active?: boolean;
    roles?: string[];
    department_id?: number;
    user_id?: number;
    department?: string;
    default_department_id?: number;
    resolution_days?: number;
};

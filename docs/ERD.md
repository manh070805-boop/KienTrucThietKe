# ERD nghiệp vụ

14 bảng nghiệp vụ; tất cả ID/FK là BIGINT UNSIGNED. Tên, kiểu cột, UNIQUE, CHECK và index thực tế xem DATA_DICTIONARY.md, xuất từ MySQL đã migrate. Bảng kỹ thuật Laravel liệt kê riêng trong tài liệu đó.

~~~mermaid
erDiagram
    users ||--o{ user_roles : "được gán"
    roles ||--o{ user_roles : "bao gồm"
    departments ||--o{ department_members : "có thành viên"
    users ||--o{ department_members : "tham gia"
    departments ||--o{ categories : "đơn vị mặc định"
    users ||--o{ tickets : "sinh viên sở hữu"
    categories |o--o{ tickets : "phân loại"
    department_members |o--o{ tickets : "cặp đơn vị và cán bộ hiện tại"
    tickets ||--o{ ticket_messages : "trao đổi"
    users ||--o{ ticket_messages : "tác giả"
    tickets ||--o{ attachments : "minh chứng"
    ticket_messages |o--o{ attachments : "tệp tin cùng hồ sơ"
    users ||--o{ attachments : "người tải"
    tickets ||--o{ ticket_assignments : "lịch sử phân công"
    department_members ||--o{ ticket_assignments : "cặp người nhận"
    tickets ||--o{ ticket_status_history : "lịch sử trạng thái"
    users ||--o{ ticket_status_history : "người thay đổi"
    users ||--o{ notifications : "người nhận"
    tickets |o--o{ notifications : "sự kiện"
    tickets ||--o| ticket_ratings : "đánh giá duy nhất"
    users |o--o{ audit_logs : "người thao tác"
    users {
        bigint id PK
        varchar full_name
        varchar email UK
        varchar student_code UK
        varchar password
        boolean is_active
    }
    roles {
        bigint id PK
        varchar code UK
        varchar name
    }
    user_roles {
        bigint user_id PK,FK
        bigint role_id PK,FK
    }
    departments {
        bigint id PK
        varchar code UK
        varchar name
        boolean is_active
    }
    department_members {
        bigint id PK
        bigint department_id FK
        bigint user_id FK
        boolean is_active
    }
    categories {
        bigint id PK
        varchar code UK
        bigint default_department_id FK
        smallint resolution_days
        boolean is_active
    }
    tickets {
        bigint id PK
        varchar code UK
        bigint student_id FK
        uuid client_request_id
        varchar type
        varchar status
        text description
        bigint category_id FK
        bigint assigned_department_id FK
        bigint assigned_staff_id FK
        timestamp submitted_at
        timestamp due_at
        timestamp resolved_at
        timestamp closed_at
        int version
        int submitted_version
    }
    ticket_messages {
        bigint id PK
        bigint ticket_id FK
        bigint author_id FK
        varchar kind
        varchar visibility
        text body
        timestamp created_at
    }
    attachments {
        bigint id PK
        bigint ticket_id FK
        bigint message_id FK
        bigint uploaded_by FK
        varchar storage_key UK
        varchar mime_type
        bigint size_bytes
    }
    ticket_assignments {
        bigint id PK
        bigint ticket_id FK
        bigint from_department_id FK
        bigint from_staff_id FK
        bigint to_department_id FK
        bigint to_staff_id FK
        bigint assigned_by FK
        varchar reason
    }
    ticket_status_history {
        bigint id PK
        bigint ticket_id FK
        varchar from_status
        varchar to_status
        bigint changed_by FK
        varchar reason
    }
    notifications {
        bigint id PK
        bigint user_id FK
        bigint ticket_id FK
        varchar event_type
        text body
        timestamp read_at
    }
    ticket_ratings {
        bigint id PK
        bigint ticket_id UK,FK
        tinyint score
        varchar comment
    }
    audit_logs {
        bigint id PK
        bigint actor_id FK
        varchar action
        varchar subject_type
        bigint subject_id
        json metadata
    }
~~~

## Ràng buộc ghép cần giải thích

- UNIQUE department_members(department_id, user_id) là khóa được tickets(assigned_department_id, assigned_staff_id) tham chiếu. Cán bộ thuộc đơn vị được bảo vệ bằng FK; trạng thái hoạt động/vai trò STAFF được kiểm tra thêm trong Service.
- Không UNIQUE cặp phân công trên tickets: một cán bộ được phụ trách nhiều hồ sơ. Migration 000002 thay UNIQUE sai bằng index thường trước khi bỏ, giữ index phục vụ FK MySQL.
- UNIQUE ticket_messages(id, ticket_id) tường minh để attachments(message_id, ticket_id) tham chiếu đúng tin nhắn cùng hồ sơ, tương thích MySQL 8.4.
- UNIQUE tickets(student_id, client_request_id), code, user email/student_code và ticket_ratings(ticket_id) ngăn trùng.
- CHECK tập loại/trạng thái/kind/visibility; cặp phân công cùng NULL/cùng có; nhánh ASSIGNED…CLOSED có phân công; nội dung hồ sơ đã gửi; score 1–5; resolution_days >0; MIME và size tệp.
- Không cascade xóa hồ sơ/tin nhắn/lịch sử khi tài khoản/danh mục thay đổi. user_roles là quan hệ quyền có thể thay bằng sync, không phải nhật ký hồ sơ. API chỉ ngừng hoạt động dữ liệu nền.

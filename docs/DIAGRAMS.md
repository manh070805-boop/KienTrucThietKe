# Sơ đồ kiến trúc và nghiệp vụ

Các sơ đồ mô tả mã thực tế; lớp/endpoint chính giữ đúng tên trong project.

## Activity — một lượt xử lý

~~~mermaid
flowchart TD
    A([Sinh viên đăng nhập]) --> B[Tạo và lưu DRAFT]
    B --> C[Thêm minh chứng private]
    C --> D{Nội dung hợp lệ?}
    D -->|Không| B
    D -->|Có| E[Submit: mã, SLA, history, notify]
    E --> F{Điều phối tiếp nhận}
    F -->|Ngoài phạm vi + lý do| R[REJECTED]
    F -->|Sinh viên rút trước phân công| X[CANCELLED]
    F -->|Người nhận hợp lệ| G[ASSIGNED]
    G --> H[Cán bộ hiện tại bắt đầu]
    H --> I[IN_PROGRESS]
    I --> J{Cần bổ sung?}
    J -->|Có| K[INFO_REQUEST + WAITING_STUDENT]
    K --> L[Sinh viên: INFO_RESPONSE]
    L --> I
    J -->|Đủ thông tin| M[RESOLUTION + RESOLVED]
    M --> N{Sinh viên kiểm tra}
    N -->|Còn lỗi + lý do| I
    N -->|Xác nhận| O[CLOSED]
    O --> P[Đánh giá một lần]
    P --> Z([Kết thúc])
~~~

## State

~~~mermaid
stateDiagram-v2
    [*] --> DRAFT
    DRAFT --> SUBMITTED: submit
    SUBMITTED --> ASSIGNED: assign
    SUBMITTED --> REJECTED: reject + reason
    SUBMITTED --> CANCELLED: cancel
    ASSIGNED --> IN_PROGRESS: start
    ASSIGNED --> ASSIGNED: transfer + reason
    IN_PROGRESS --> ASSIGNED: transfer + reason
    IN_PROGRESS --> WAITING_STUDENT: request-information + body
    WAITING_STUDENT --> IN_PROGRESS: messages của chủ hồ sơ
    IN_PROGRESS --> RESOLVED: resolve + body
    RESOLVED --> IN_PROGRESS: reopen + reason
    RESOLVED --> CLOSED: close của chủ hồ sơ
    CLOSED --> [*]
    REJECTED --> [*]
    CANCELLED --> [*]
~~~

messages/internal-note/classify/rate có thể tăng version mà không đổi trạng thái. Không có API đặt status tùy ý.

## Class — trọng tâm quy trình

~~~mermaid
classDiagram
    class TicketController {
        index()
        store()
        show()
        update()
        action()
    }
    class TicketRequest {
        rules()
    }
    class TicketService {
        create()
        execute()
        assertVersion()
    }
    class Workflow {
        TRANSITIONS
        target()
    }
    class TicketPolicy {
        view()
        create()
        act()
    }
    class Access {
        scope()
        owner()
        staff()
        canSeeInternal()
    }
    class TicketResource {
        toArray()
    }
    class Ticket {
        status
        version
        submitted_version
        due_at
        messages()
        statusHistory()
        assignments()
        rating()
    }
    class AttachmentService {
        upload()
        delete()
        canDownload()
    }
    class ReportingService {
        summarize()
    }
    TicketController --> TicketRequest
    TicketController --> TicketService
    TicketController --> TicketResource
    TicketService --> TicketPolicy
    TicketPolicy --> Access
    TicketService --> Workflow
    TicketService --> Ticket
    TicketResource --> Access
    AttachmentService --> Access
    AttachmentService --> TicketService
    ReportingService --> Ticket
~~~

## Sequence — gửi hồ sơ

~~~mermaid
sequenceDiagram
    actor SV as Sinh viên
    participant UI as DraftEditor
    participant API as TicketController
    participant REQ as TicketRequest
    participant S as TicketService
    participant P as TicketPolicy
    participant DB as MySQL
    SV->>UI: Bấm gửi
    UI->>API: PUT draft + version + nội dung
    API->>REQ: validate
    API->>S: execute(update)
    S->>DB: BEGIN, SELECT FOR UPDATE
    S->>P: chủ nháp + tài khoản/role
    S->>DB: lưu nội dung, version++, audit, COMMIT
    UI->>API: POST submit chỉ version
    API->>S: execute(submit)
    S->>DB: BEGIN, SELECT FOR UPDATE
    S->>P: quyền submit hiện tại
    alt retry đúng submitted_version
        S-->>API: trả hồ sơ hiện tại, không tạo thêm
    else nháp hợp lệ
        S->>DB: mã, submitted_at, due_at, version++
        S->>DB: history + notify + audit, COMMIT
    end
    API-->>UI: TicketResource thuộc quyền
~~~

## Sequence — phân công/chuyển

~~~mermaid
sequenceDiagram
    actor DP as Điều phối
    participant API as TicketController
    participant S as TicketService
    participant P as TicketPolicy
    participant DB as MySQL
    DP->>API: assign hoặc transfer + version
    API->>S: dữ liệu đã validate
    S->>DB: BEGIN, khóa ticket
    S->>P: COORDINATOR, không DRAFT
    S->>S: kiểm version + Workflow
    S->>DB: đọc thành viên STAFF hoạt động bằng shared lock
    S->>DB: ghi assignment, current staff/department
    S->>DB: ASSIGNED + version++, history kể cả cùng ASSIGNED
    S->>DB: notify + audit, COMMIT
    API-->>DP: Resource mới
    Note over P,DB: Cán bộ cũ không còn thỏa Access::staff/scope khi đọc/file/notification/report
~~~

## Sequence — trả lời và đóng

~~~mermaid
sequenceDiagram
    actor CB as Cán bộ hiện tại
    actor SV as Sinh viên chủ hồ sơ
    participant S as TicketService
    participant DB as MySQL
    CB->>S: resolve(body, version)
    S->>DB: BEGIN + khóa, Policy + version + trạng thái
    S->>DB: RESOLUTION, RESOLVED, resolved_at, history, notify, audit
    S->>DB: COMMIT
    SV->>S: close(version)
    S->>DB: BEGIN + khóa, kiểm chủ hồ sơ và RESOLVED
    S->>DB: CLOSED, closed_at, version++, history, notify, audit, COMMIT
    SV->>S: rate(score, comment, version)
    S->>DB: BEGIN + khóa, CLOSED + chủ + chưa đánh giá
    S->>DB: rating UNIQUE, version++, audit, COMMIT
~~~

## Sequence — xử lý lại

~~~mermaid
sequenceDiagram
    actor SV as Chủ hồ sơ
    participant API as TicketController
    participant S as TicketService
    participant DB as MySQL
    SV->>API: POST reopen(reason, version)
    API->>S: execute(reopen)
    S->>DB: BEGIN + khóa ticket
    S->>S: Policy chủ + version + RESOLVED
    S->>DB: COMMENT lý do công khai
    S->>DB: IN_PROGRESS, resolved_at=NULL, version++
    Note over S,DB: due_at và các RESOLUTION/history cũ giữ nguyên
    S->>DB: history + notify + audit, COMMIT
    API-->>SV: Resource + tiến độ mới
~~~

## C4 Context — hệ thống trong môi trường

~~~mermaid
flowchart LR
    SV[Sinh viên] -->|gửi, bổ sung, xác nhận| SYS[Student Voice / Hệ thống tiếp nhận]
    DP[Điều phối] -->|phân loại, phân công, báo cáo| SYS
    CB[Cán bộ đơn vị] -->|xử lý hồ sơ hiện được giao| SYS
    AD[Quản trị] -->|tài khoản và dữ liệu nền| SYS
    SYS -->|hồ sơ và tiến độ theo quyền| SV
~~~

## C4 Container — các phần chạy

~~~mermaid
flowchart LR
    U[Người dùng] --> SPA[React + TypeScript / trình duyệt]
    subgraph SERVER[Ứng dụng Laravel cùng origin]
        HTTP[Routes + web session + CSRF]
        ID[Identity: IdentityService]
        ORG[Organization: AdminService + Directory]
        T[Tickets: Policy + TicketService + Workflow]
        A[Attachments: AttachmentService]
        N[Notifications: controller + sự kiện trong Service]
        R[Reporting: TicketQuery + ReportingService]
    end
    SPA -->|HTTPS khi triển khai / HTTP local| HTTP
    HTTP --> ID
    HTTP --> ORG
    HTTP --> T
    HTTP --> A
    HTTP --> N
    HTTP --> R
    ORG --> DB[(MySQL 8.4)]
    T --> DB
    ID --> DB
    N --> DB
    R --> DB
    A --> DB
    A --> F[(Private filesystem)]
~~~

Đây là một Modular Monolith: một ứng dụng, một database; phân chia theo trách nhiệm bằng các Service/Policy/Request/Resource. Giữ cấu trúc thư mục Laravel có sẵn, chưa tách thành package độc lập hay microservice. Controller/Models dùng chung không đồng nghĩa mỗi bảng là một service mạng.

## Deployment local

~~~mermaid
flowchart TD
    B[Chrome / desktop hoặc mobile viewport] -->|127.0.0.1:8000| L[PHP artisan serve / Laravel]
    B -.->|dev.bat: HMR 5173| V[Vite]
    L -->|TCP 127.0.0.1:3307| M[MySQL 8.4.3 instance riêng]
    M --> DEV[(student_feedback)]
    M --> TEST[(student_feedback_test)]
    L --> FS[storage/app/private]
    M --> DATA[storage/app/mysql-data]
    E[Chrome Playwright E2E] -->|8001, env e2e| EL[Laravel test server]
    EL --> TEST
    EL --> EFS[storage/app/e2e-private]
~~~

Bản Compose chỉ cung cấp MySQL ở 3308, chưa được chạy trên máy này. Triển khai thực tế cần web server, HTTPS và cấu hình vận hành riêng; sơ đồ local không phải tuyên bố production.

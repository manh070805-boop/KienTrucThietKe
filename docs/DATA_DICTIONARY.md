# Từ điển dữ liệu và ràng buộc MySQL

Sinh tự động bằng php scripts/export-design.php từ migrations đã chạy trên student_feedback (MySQL 8.4.3). Không phải thiết kế SQL độc lập.

14 bảng nghiệp vụ: users, roles, user_roles, departments, department_members, categories, tickets, ticket_messages, attachments, ticket_assignments, ticket_status_history, notifications, ticket_ratings, audit_logs.

Bảng kỹ thuật Laravel: migrations, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs. users.name, email_verified_at, remember_token giữ tương thích Laravel.

## users

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| full_name | varchar(255) | NO | NULL |  | Họ tên hiển thị |
| name | varchar(255) | NO | NULL |  | Tên tương thích Laravel/tên dữ liệu nền |
| email | varchar(255) | NO | NULL | UNI | Email đăng nhập duy nhất |
| student_code | varchar(255) | YES | NULL | UNI | Mã sinh viên duy nhất khi có |
| email_verified_at | timestamp | YES | NULL |  | Trường kỹ thuật Laravel; MVP không dùng xác minh email |
| password | varchar(255) | NO | NULL |  | Mật khẩu đã băm |
| remember_token | varchar(100) | YES | NULL |  | Trường kỹ thuật Laravel, không trả API |
| is_active | tinyint(1) | NO | 1 |  | Cho phép sử dụng khi còn hoạt động |
| created_at | timestamp | YES | NULL |  | Thời điểm tạo UTC |
| updated_at | timestamp | YES | NULL |  | Thời điểm cập nhật UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_student_code_unique` (`student_code`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## roles

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| code | varchar(30) | NO | NULL | UNI | Mã duy nhất; hồ sơ chỉ có mã sau khi gửi |
| name | varchar(100) | NO | NULL |  | Tên tương thích Laravel/tên dữ liệu nền |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## user_roles

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| user_id | bigint unsigned | NO | NULL | PRI | Tài khoản |
| role_id | bigint unsigned | NO | NULL | PRI | Vai trò |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `user_roles` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## departments

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| code | varchar(30) | NO | NULL | UNI | Mã duy nhất; hồ sơ chỉ có mã sau khi gửi |
| name | varchar(150) | NO | NULL |  | Tên tương thích Laravel/tên dữ liệu nền |
| is_active | tinyint(1) | NO | 1 |  | Cho phép sử dụng khi còn hoạt động |
| created_at | timestamp | YES | NULL |  | Thời điểm tạo UTC |
| updated_at | timestamp | YES | NULL |  | Thời điểm cập nhật UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## department_members

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| department_id | bigint unsigned | NO | NULL | MUL | Đơn vị thành viên |
| user_id | bigint unsigned | NO | NULL | MUL | Tài khoản |
| is_active | tinyint(1) | NO | 1 |  | Cho phép sử dụng khi còn hoạt động |
| created_at | timestamp | YES | NULL |  | Thời điểm tạo UTC |
| updated_at | timestamp | YES | NULL |  | Thời điểm cập nhật UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `department_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_members_department_id_user_id_unique` (`department_id`,`user_id`),
  KEY `department_members_user_id_foreign` (`user_id`),
  CONSTRAINT `department_members_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `department_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## categories

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| code | varchar(40) | NO | NULL | UNI | Mã duy nhất; hồ sơ chỉ có mã sau khi gửi |
| name | varchar(150) | NO | NULL |  | Tên tương thích Laravel/tên dữ liệu nền |
| default_department_id | bigint unsigned | NO | NULL | MUL | Đơn vị mặc định của danh mục |
| resolution_days | smallint unsigned | NO | 7 |  | SLA số ngày lịch, lớn hơn 0 |
| is_active | tinyint(1) | NO | 1 |  | Cho phép sử dụng khi còn hoạt động |
| created_at | timestamp | YES | NULL |  | Thời điểm tạo UTC |
| updated_at | timestamp | YES | NULL |  | Thời điểm cập nhật UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_department_id` bigint unsigned NOT NULL,
  `resolution_days` smallint unsigned NOT NULL DEFAULT '7',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_unique` (`code`),
  KEY `categories_default_department_id_foreign` (`default_department_id`),
  CONSTRAINT `categories_default_department_id_foreign` FOREIGN KEY (`default_department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `categories_resolution_days_chk` CHECK ((`resolution_days` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## tickets

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| code | varchar(30) | YES | NULL | UNI | Mã duy nhất; hồ sơ chỉ có mã sau khi gửi |
| student_id | bigint unsigned | NO | NULL | MUL | Chủ hồ sơ |
| client_request_id | char(36) | NO | NULL |  | UUID ổn định cho retry tạo nháp |
| type | varchar(20) | NO | NULL |  | FEEDBACK hoặc PROPOSAL |
| title | varchar(255) | YES | NULL |  | Tiêu đề/nội dung thông báo |
| description | text | YES | NULL |  | Nội dung gốc bất biến sau gửi |
| category_id | bigint unsigned | YES | NULL | MUL | Danh mục hiện tại |
| status | varchar(30) | NO | DRAFT | MUL | Trạng thái theo Workflow |
| assigned_department_id | bigint unsigned | YES | NULL | MUL | Đơn vị hiện phụ trách |
| assigned_staff_id | bigint unsigned | YES | NULL | MUL | Cán bộ hiện phụ trách |
| submitted_at | timestamp | YES | NULL |  | Thời điểm gửi UTC |
| due_at | timestamp | YES | NULL |  | Hạn cố định UTC từ SLA danh mục |
| resolved_at | timestamp | YES | NULL |  | Kết quả hiện tại; xóa khi xử lý lại |
| closed_at | timestamp | YES | NULL |  | Thời điểm sinh viên xác nhận đóng |
| version | int unsigned | NO | 1 |  | Phiên bản tăng mỗi cập nhật |
| created_at | timestamp | YES | NULL |  | Thời điểm tạo UTC |
| updated_at | timestamp | YES | NULL |  | Thời điểm cập nhật UTC |
| submitted_version | int unsigned | YES | NULL |  | Phiên bản trước lần gửi thành công; nhận diện retry |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `client_request_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `assigned_department_id` bigint unsigned DEFAULT NULL,
  `assigned_staff_id` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `submitted_version` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_student_id_client_request_id_unique` (`student_id`,`client_request_id`),
  UNIQUE KEY `tickets_code_unique` (`code`),
  KEY `tickets_student_id_created_at_id_index` (`student_id`,`created_at`,`id`),
  KEY `tickets_status_submitted_at_id_index` (`status`,`submitted_at`,`id`),
  KEY `tickets_assigned_staff_id_status_updated_at_id_index` (`assigned_staff_id`,`status`,`updated_at`,`id`),
  KEY `tickets_assigned_department_id_status_index` (`assigned_department_id`,`status`),
  KEY `tickets_status_due_at_index` (`status`,`due_at`),
  KEY `tickets_category_id_foreign` (`category_id`),
  KEY `tickets_assignment_pair_index` (`assigned_department_id`,`assigned_staff_id`),
  CONSTRAINT `tickets_assignment_member_fk` FOREIGN KEY (`assigned_department_id`, `assigned_staff_id`) REFERENCES `department_members` (`department_id`, `user_id`) ON DELETE RESTRICT,
  CONSTRAINT `tickets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tickets_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tickets_assigned_status_chk` CHECK (((`status` not in (_utf8mb4'ASSIGNED',_utf8mb4'IN_PROGRESS',_utf8mb4'WAITING_STUDENT',_utf8mb4'RESOLVED',_utf8mb4'CLOSED')) or ((`assigned_department_id` is not null) and (`assigned_staff_id` is not null)))),
  CONSTRAINT `tickets_assignment_pair_chk` CHECK ((((`assigned_department_id` is null) and (`assigned_staff_id` is null)) or ((`assigned_department_id` is not null) and (`assigned_staff_id` is not null)))),
  CONSTRAINT `tickets_status_chk` CHECK ((`status` in (_utf8mb4'DRAFT',_utf8mb4'SUBMITTED',_utf8mb4'ASSIGNED',_utf8mb4'IN_PROGRESS',_utf8mb4'WAITING_STUDENT',_utf8mb4'RESOLVED',_utf8mb4'CLOSED',_utf8mb4'REJECTED',_utf8mb4'CANCELLED'))),
  CONSTRAINT `tickets_submitted_content_chk` CHECK (((`status` = _utf8mb4'DRAFT') or ((`code` is not null) and (`title` is not null) and (char_length(trim(`title`)) > 0) and (`description` is not null) and (char_length(trim(`description`)) > 0) and (`category_id` is not null) and (`submitted_at` is not null) and (`due_at` is not null)))),
  CONSTRAINT `tickets_type_chk` CHECK ((`type` in (_utf8mb4'FEEDBACK',_utf8mb4'PROPOSAL'))),
  CONSTRAINT `tickets_version_chk` CHECK ((`version` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## ticket_messages

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| ticket_id | bigint unsigned | NO | NULL | MUL | Hồ sơ liên quan |
| author_id | bigint unsigned | NO | NULL | MUL | Người tạo tin nhắn |
| kind | varchar(30) | NO | NULL |  | COMMENT/INFO_REQUEST/INFO_RESPONSE/RESOLUTION |
| visibility | varchar(30) | NO | NULL |  | STUDENT_VISIBLE hoặc STAFF_ONLY |
| body | text | NO | NULL |  | Nội dung trao đổi/thông báo |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibility` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_messages_id_ticket_unique` (`id`,`ticket_id`),
  KEY `ticket_messages_ticket_id_created_at_id_index` (`ticket_id`,`created_at`,`id`),
  KEY `ticket_messages_author_id_foreign` (`author_id`),
  CONSTRAINT `ticket_messages_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_messages_kind_chk` CHECK ((`kind` in (_utf8mb4'COMMENT',_utf8mb4'INFO_REQUEST',_utf8mb4'INFO_RESPONSE',_utf8mb4'RESOLUTION'))),
  CONSTRAINT `ticket_messages_visibility_chk` CHECK ((`visibility` in (_utf8mb4'STUDENT_VISIBLE',_utf8mb4'STAFF_ONLY')))
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## attachments

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| ticket_id | bigint unsigned | NO | NULL | MUL | Hồ sơ liên quan |
| message_id | bigint unsigned | YES | NULL | MUL | Tin nhắn cùng hồ sơ; NULL là minh chứng gốc |
| uploaded_by | bigint unsigned | NO | NULL | MUL | Người tải tệp |
| original_name | varchar(255) | NO | NULL |  | Tên hiển thị của tệp |
| storage_key | varchar(255) | NO | NULL | UNI | Khóa riêng tư do server sinh |
| mime_type | varchar(100) | NO | NULL |  | Loại nội dung đã kiểm tra |
| size_bytes | bigint unsigned | NO | NULL |  | Dung lượng 1–5242880 byte |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `message_id` bigint unsigned DEFAULT NULL,
  `uploaded_by` bigint unsigned NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attachments_storage_key_unique` (`storage_key`),
  KEY `attachments_ticket_id_foreign` (`ticket_id`),
  KEY `attachments_message_ticket_fk` (`message_id`,`ticket_id`),
  KEY `attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `attachments_message_ticket_fk` FOREIGN KEY (`message_id`, `ticket_id`) REFERENCES `ticket_messages` (`id`, `ticket_id`) ON DELETE RESTRICT,
  CONSTRAINT `attachments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attachments_mime_chk` CHECK ((`mime_type` in (_utf8mb4'application/pdf',_utf8mb4'image/jpeg',_utf8mb4'image/png'))),
  CONSTRAINT `attachments_size_chk` CHECK (((`size_bytes` > 0) and (`size_bytes` <= 5242880)))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## ticket_assignments

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| ticket_id | bigint unsigned | NO | NULL | MUL | Hồ sơ liên quan |
| from_department_id | bigint unsigned | YES | NULL | MUL | Đơn vị trước chuyển |
| from_staff_id | bigint unsigned | YES | NULL |  | Cán bộ trước chuyển |
| to_department_id | bigint unsigned | NO | NULL | MUL | Đơn vị mới |
| to_staff_id | bigint unsigned | NO | NULL |  | Cán bộ mới |
| assigned_by | bigint unsigned | NO | NULL | MUL | Điều phối phân công |
| reason | varchar(500) | YES | NULL |  | Lý do nghiệp vụ; lý do phân công là nội bộ |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `ticket_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `from_department_id` bigint unsigned DEFAULT NULL,
  `from_staff_id` bigint unsigned DEFAULT NULL,
  `to_department_id` bigint unsigned NOT NULL,
  `to_staff_id` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_assignments_ticket_id_created_at_id_index` (`ticket_id`,`created_at`,`id`),
  KEY `assignments_from_member_fk` (`from_department_id`,`from_staff_id`),
  KEY `assignments_to_member_fk` (`to_department_id`,`to_staff_id`),
  KEY `ticket_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `assignments_from_member_fk` FOREIGN KEY (`from_department_id`, `from_staff_id`) REFERENCES `department_members` (`department_id`, `user_id`) ON DELETE RESTRICT,
  CONSTRAINT `assignments_to_member_fk` FOREIGN KEY (`to_department_id`, `to_staff_id`) REFERENCES `department_members` (`department_id`, `user_id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_assignments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assignments_from_pair_chk` CHECK ((((`from_department_id` is null) and (`from_staff_id` is null)) or ((`from_department_id` is not null) and (`from_staff_id` is not null))))
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## ticket_status_history

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| ticket_id | bigint unsigned | NO | NULL | MUL | Hồ sơ liên quan |
| from_status | varchar(30) | YES | NULL |  | Trạng thái trước; NULL ở khởi tạo |
| to_status | varchar(30) | NO | NULL |  | Trạng thái sau |
| changed_by | bigint unsigned | NO | NULL | MUL | Người chuyển trạng thái |
| reason | varchar(500) | YES | NULL |  | Lý do nghiệp vụ; lý do phân công là nội bộ |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `ticket_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `from_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_status_history_ticket_id_created_at_id_index` (`ticket_id`,`created_at`,`id`),
  KEY `ticket_status_history_changed_by_foreign` (`changed_by`),
  CONSTRAINT `ticket_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_status_history_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `status_history_from_chk` CHECK (((`from_status` is null) or (`from_status` in (_utf8mb4'DRAFT',_utf8mb4'SUBMITTED',_utf8mb4'ASSIGNED',_utf8mb4'IN_PROGRESS',_utf8mb4'WAITING_STUDENT',_utf8mb4'RESOLVED',_utf8mb4'CLOSED',_utf8mb4'REJECTED',_utf8mb4'CANCELLED')))),
  CONSTRAINT `status_history_to_chk` CHECK ((`to_status` in (_utf8mb4'DRAFT',_utf8mb4'SUBMITTED',_utf8mb4'ASSIGNED',_utf8mb4'IN_PROGRESS',_utf8mb4'WAITING_STUDENT',_utf8mb4'RESOLVED',_utf8mb4'CLOSED',_utf8mb4'REJECTED',_utf8mb4'CANCELLED')))
) ENGINE=InnoDB AUTO_INCREMENT=454 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## notifications

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| user_id | bigint unsigned | NO | NULL | MUL | Tài khoản |
| ticket_id | bigint unsigned | YES | NULL | MUL | Hồ sơ liên quan |
| event_type | varchar(60) | NO | NULL |  | Loại sự kiện |
| title | varchar(255) | NO | NULL |  | Tiêu đề/nội dung thông báo |
| body | text | NO | NULL |  | Nội dung trao đổi/thông báo |
| read_at | timestamp | YES | NULL |  | Thời điểm đọc; NULL là chưa đọc |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_created_at_index` (`user_id`,`read_at`,`created_at`),
  KEY `notifications_ticket_id_foreign` (`ticket_id`),
  CONSTRAINT `notifications_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=416 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## ticket_ratings

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| ticket_id | bigint unsigned | NO | NULL | UNI | Hồ sơ liên quan |
| score | tinyint unsigned | NO | NULL |  | Điểm 1–5 |
| comment | varchar(1000) | YES | NULL |  | Nhận xét không bắt buộc |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `ticket_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `score` tinyint unsigned NOT NULL,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_ratings_ticket_id_unique` (`ticket_id`),
  CONSTRAINT `ticket_ratings_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_ratings_score_chk` CHECK ((`score` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

## audit_logs

| Trường | Kiểu | NULL | Mặc định | Khóa | Ý nghĩa |
|---|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI | Khóa định danh |
| actor_id | bigint unsigned | YES | NULL | MUL | Người thao tác |
| action | varchar(100) | NO | NULL |  | Tên use case được ghi nhận |
| subject_type | varchar(150) | YES | NULL | MUL | Loại đối tượng audit |
| subject_id | bigint unsigned | YES | NULL |  | ID đối tượng audit |
| metadata | json | YES | NULL |  | Ngữ cảnh JSON; không lưu bí mật/nội dung nhạy cảm |
| created_at | timestamp | NO | CURRENT_TIMESTAMP |  | Thời điểm tạo UTC |

<details><summary>DDL thực tế (UNIQUE, INDEX, FK và CHECK)</summary>

~~~sql
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=622 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
~~~
</details>

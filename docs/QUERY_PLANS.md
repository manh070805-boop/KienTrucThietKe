# EXPLAIN truy vấn thực tế

Dataset demo 100 hồ sơ; đây là kiểm tra kế hoạch truy vấn, không phải đo tải 10.000 hồ sơ/50 người.

## Hồ sơ của sinh viên

~~~sql
SELECT id,title,status FROM tickets WHERE student_id=? ORDER BY created_at DESC,id DESC LIMIT 15
~~~

Tham số: [1]

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "1.75"
    },
    "ordering_operation": {
      "using_filesort": false,
      "table": {
        "table_name": "tickets",
        "access_type": "ref",
        "possible_keys": [
          "tickets_student_id_client_request_id_unique",
          "tickets_student_id_created_at_id_index"
        ],
        "key": "tickets_student_id_created_at_id_index",
        "used_key_parts": [
          "student_id"
        ],
        "key_length": "8",
        "ref": [
          "const"
        ],
        "rows_examined_per_scan": 5,
        "rows_produced_per_join": 5,
        "filtered": "100.00",
        "backward_index_scan": true,
        "cost_info": {
          "read_cost": "1.25",
          "eval_cost": "0.50",
          "prefix_cost": "1.75",
          "data_read_per_join": "7K"
        },
        "used_columns": [
          "id",
          "student_id",
          "title",
          "status",
          "created_at"
        ]
      }
    }
  }
}
~~~

## Hàng đợi

~~~sql
SELECT id,title,status FROM tickets WHERE status='SUBMITTED' ORDER BY submitted_at DESC,id DESC LIMIT 15
~~~

Tham số: []

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "3.70"
    },
    "ordering_operation": {
      "using_filesort": false,
      "table": {
        "table_name": "tickets",
        "access_type": "ref",
        "possible_keys": [
          "tickets_status_submitted_at_id_index",
          "tickets_status_due_at_index"
        ],
        "key": "tickets_status_submitted_at_id_index",
        "used_key_parts": [
          "status"
        ],
        "key_length": "122",
        "ref": [
          "const"
        ],
        "rows_examined_per_scan": 12,
        "rows_produced_per_join": 12,
        "filtered": "100.00",
        "backward_index_scan": true,
        "cost_info": {
          "read_cost": "2.50",
          "eval_cost": "1.20",
          "prefix_cost": "3.70",
          "data_read_per_join": "18K"
        },
        "used_columns": [
          "id",
          "title",
          "status",
          "submitted_at"
        ]
      }
    }
  }
}
~~~

## Công việc được giao

~~~sql
SELECT id,title,status FROM tickets WHERE assigned_staff_id=? AND status='IN_PROGRESS' ORDER BY updated_at DESC,id DESC LIMIT 15
~~~

Tham số: [23]

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "2.10"
    },
    "ordering_operation": {
      "using_filesort": false,
      "table": {
        "table_name": "tickets",
        "access_type": "ref",
        "possible_keys": [
          "tickets_status_submitted_at_id_index",
          "tickets_assigned_staff_id_status_updated_at_id_index",
          "tickets_status_due_at_index"
        ],
        "key": "tickets_assigned_staff_id_status_updated_at_id_index",
        "used_key_parts": [
          "assigned_staff_id",
          "status"
        ],
        "key_length": "131",
        "ref": [
          "const",
          "const"
        ],
        "rows_examined_per_scan": 6,
        "rows_produced_per_join": 6,
        "filtered": "100.00",
        "backward_index_scan": true,
        "cost_info": {
          "read_cost": "1.50",
          "eval_cost": "0.60",
          "prefix_cost": "2.10",
          "data_read_per_join": "9K"
        },
        "used_columns": [
          "id",
          "title",
          "status",
          "assigned_staff_id",
          "updated_at"
        ]
      }
    }
  }
}
~~~

## Đang mở quá hạn

~~~sql
SELECT id FROM tickets WHERE status IN ('SUBMITTED','ASSIGNED','IN_PROGRESS','WAITING_STUDENT') AND due_at < UTC_TIMESTAMP()
~~~

Tham số: []

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "2.91"
    },
    "table": {
      "table_name": "tickets",
      "access_type": "range",
      "possible_keys": [
        "tickets_status_submitted_at_id_index",
        "tickets_status_due_at_index"
      ],
      "key": "tickets_status_due_at_index",
      "used_key_parts": [
        "status",
        "due_at"
      ],
      "key_length": "127",
      "rows_examined_per_scan": 13,
      "rows_produced_per_join": 13,
      "filtered": "100.00",
      "using_index": true,
      "cost_info": {
        "read_cost": "1.61",
        "eval_cost": "1.30",
        "prefix_cost": "2.91",
        "data_read_per_join": "20K"
      },
      "used_columns": [
        "id",
        "status",
        "due_at"
      ],
      "attached_condition": "((`student_feedback`.`tickets`.`status` in ('SUBMITTED','ASSIGNED','IN_PROGRESS','WAITING_STUDENT')) and (`student_feedback`.`tickets`.`due_at` < <cache>(utc_timestamp())))"
    }
  }
}
~~~

## Dòng thời gian

~~~sql
SELECT id,body FROM ticket_messages WHERE ticket_id=33 ORDER BY created_at,id
~~~

Tham số: []

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "1.05"
    },
    "ordering_operation": {
      "using_filesort": false,
      "table": {
        "table_name": "ticket_messages",
        "access_type": "ref",
        "possible_keys": [
          "ticket_messages_ticket_id_created_at_id_index"
        ],
        "key": "ticket_messages_ticket_id_created_at_id_index",
        "used_key_parts": [
          "ticket_id"
        ],
        "key_length": "8",
        "ref": [
          "const"
        ],
        "rows_examined_per_scan": 3,
        "rows_produced_per_join": 3,
        "filtered": "100.00",
        "cost_info": {
          "read_cost": "0.75",
          "eval_cost": "0.30",
          "prefix_cost": "1.05",
          "data_read_per_join": "864"
        },
        "used_columns": [
          "id",
          "ticket_id",
          "body",
          "created_at"
        ]
      }
    }
  }
}
~~~

## Thông báo chưa đọc

~~~sql
SELECT id FROM notifications WHERE user_id=? AND read_at IS NULL ORDER BY created_at DESC LIMIT 20
~~~

Tham số: [1]

~~~json
{
  "query_block": {
    "select_id": 1,
    "cost_info": {
      "query_cost": "0.95"
    },
    "ordering_operation": {
      "using_filesort": false,
      "table": {
        "table_name": "notifications",
        "access_type": "ref",
        "possible_keys": [
          "notifications_user_id_read_at_created_at_index"
        ],
        "key": "notifications_user_id_read_at_created_at_index",
        "used_key_parts": [
          "user_id",
          "read_at"
        ],
        "key_length": "13",
        "ref": [
          "const",
          "const"
        ],
        "rows_examined_per_scan": 7,
        "rows_produced_per_join": 7,
        "filtered": "100.00",
        "backward_index_scan": true,
        "using_index": true,
        "cost_info": {
          "read_cost": "0.25",
          "eval_cost": "0.70",
          "prefix_cost": "0.95",
          "data_read_per_join": "8K"
        },
        "used_columns": [
          "id",
          "user_id",
          "read_at",
          "created_at"
        ],
        "attached_condition": "(`student_feedback`.`notifications`.`read_at` is null)"
      }
    }
  }
}
~~~

Index được giữ theo truy vấn và FK. Với bảng nhỏ, optimizer có thể chọn quét bảng; chưa chứng minh hiệu năng ở tải lớn. Xem key/possible_keys và rows_examined_per_scan trong kết quả trên.

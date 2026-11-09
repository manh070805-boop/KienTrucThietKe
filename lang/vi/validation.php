<?php
return [
    'required'=>':attribute là bắt buộc.','email'=>':attribute phải là email hợp lệ.','string'=>':attribute phải là chuỗi.',
    'integer'=>':attribute phải là số nguyên.','boolean'=>':attribute phải là đúng hoặc sai.','uuid'=>':attribute phải là UUID hợp lệ.',
    'unique'=>':attribute đã tồn tại.','exists'=>':attribute không tồn tại.','in'=>':attribute không hợp lệ.',
    'prohibited'=>'Không được gửi :attribute trong thao tác này.','array'=>':attribute phải là danh sách.',
    'file'=>':attribute phải là tệp hợp lệ.','uploaded'=>':attribute tải lên thất bại.',
    'min'=>['string'=>':attribute phải có ít nhất :min ký tự.','numeric'=>':attribute phải từ :min trở lên.','array'=>':attribute cần ít nhất :min phần tử.'],
    'max'=>['string'=>':attribute tối đa :max ký tự.','numeric'=>':attribute không được lớn hơn :max.','file'=>':attribute tối đa :max KB.'],
    'between'=>['numeric'=>':attribute phải từ :min đến :max.'],
    'date_format'=>':attribute phải đúng định dạng :format.','after_or_equal'=>':attribute không được trước :date.',
    'alpha_dash'=>':attribute chỉ gồm chữ, số, dấu gạch ngang và gạch dưới.',
    'attributes'=>['title'=>'Tiêu đề','description'=>'Nội dung','category_id'=>'Danh mục','version'=>'Phiên bản','password'=>'Mật khẩu',
        'email'=>'Email','roles'=>'Vai trò','full_name'=>'Họ tên','reason'=>'Lý do','body'=>'Nội dung','file'=>'Tệp',
        'department_id'=>'Đơn vị','staff_id'=>'Cán bộ','resolution_days'=>'Số ngày xử lý','name'=>'Tên','code'=>'Mã','score'=>'Điểm',
        'from'=>'Ngày bắt đầu','to'=>'Ngày kết thúc','student_code'=>'Mã sinh viên','user_id'=>'Tài khoản','is_active'=>'Trạng thái hoạt động'],
];

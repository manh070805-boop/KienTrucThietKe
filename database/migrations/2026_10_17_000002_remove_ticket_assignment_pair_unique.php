<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL needs a replacement index before dropping an index used by the FK.
        Schema::table('tickets', fn (Blueprint $table) => $table->index(['assigned_department_id', 'assigned_staff_id'], 'tickets_assignment_pair_index'));
        Schema::table('tickets', fn (Blueprint $table) => $table->dropUnique('tickets_assignment_pair_unique'));
    }

    public function down(): void
    {
        // Adding uniqueness would destroy compatibility with valid multiple assignments.
        throw new RuntimeException('Migration sửa lỗi không hỗ trợ rollback. Khôi phục bản sao lưu nếu cần.');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $checks = [
            'tickets' => [
                'tickets_assigned_status_chk' => "status NOT IN ('ASSIGNED','IN_PROGRESS','WAITING_STUDENT','RESOLVED','CLOSED') OR (assigned_department_id IS NOT NULL AND assigned_staff_id IS NOT NULL)",
                'tickets_submitted_content_chk' => "status = 'DRAFT' OR (code IS NOT NULL AND title IS NOT NULL AND CHAR_LENGTH(TRIM(title)) > 0 AND description IS NOT NULL AND CHAR_LENGTH(TRIM(description)) > 0 AND category_id IS NOT NULL AND submitted_at IS NOT NULL AND due_at IS NOT NULL)",
                'tickets_version_chk' => 'version > 0',
            ],
            'attachments' => ['attachments_mime_chk' => "mime_type IN ('application/pdf','image/jpeg','image/png')"],
            'ticket_status_history' => ['status_history_from_chk' => "from_status IS NULL OR from_status IN ('DRAFT','SUBMITTED','ASSIGNED','IN_PROGRESS','WAITING_STUDENT','RESOLVED','CLOSED','REJECTED','CANCELLED')"],
            'ticket_assignments' => ['assignments_from_pair_chk' => '(from_department_id IS NULL AND from_staff_id IS NULL) OR (from_department_id IS NOT NULL AND from_staff_id IS NOT NULL)'],
        ];
        foreach ($checks as $table => $rules) {
            foreach ($rules as $name => $expression) {
                DB::statement("ALTER TABLE $table ADD CONSTRAINT $name CHECK ($expression)");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach (['tickets' => ['tickets_assigned_status_chk', 'tickets_submitted_content_chk', 'tickets_version_chk'], 'attachments' => ['attachments_mime_chk'], 'ticket_status_history' => ['status_history_from_chk'], 'ticket_assignments' => ['assignments_from_pair_chk']] as $table => $names) {
            foreach ($names as $name) {
                DB::statement("ALTER TABLE $table DROP CHECK $name");
            }
        }
    }
};

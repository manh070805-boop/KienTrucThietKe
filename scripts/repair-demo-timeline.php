<?php

use App\Models\AuditLog;
use App\Models\Ticket;
use Database\Seeders\FeedbackSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Correct only the demo owned by the old seed, after backup. Not a general-purpose data update.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! in_array(DB::connection()->getDatabaseName(), ['student_feedback', 'student_feedback_test'], true) || ! app()->environment('local', 'e2e')) {
    throw new RuntimeException('Chỉ dùng cho hai database demo/test riêng đã tạo.');
}
$seed = AuditLog::where('action', 'demo.seed')->latest('id')->firstOrFail();
if (($seed->metadata['timeline_version'] ?? 0) >= 2) {
    echo "Timeline đã dùng UTC, không sửa lại.\n";
    exit;
}
DB::transaction(function () use ($seed) {
    $ids = Ticket::where('client_request_id', 'like', FeedbackSeeder::PREFIX.'%')->pluck('id');
    foreach (['created_at', 'updated_at', 'submitted_at', 'due_at', 'resolved_at', 'closed_at'] as $column) {
        DB::table('tickets')->whereIn('id', $ids)->update([$column => DB::raw("DATE_ADD($column, INTERVAL 7 HOUR)")]);
    }
    foreach (['users' => DB::table('users')->where('email', 'like', '%@demo.local')->pluck('id'),
        'departments' => DB::table('departments')->whereIn('code', ['PCTSV', 'PDT', 'CNTT'])->pluck('id'),
        'categories' => DB::table('categories')->whereIn('code', ['HOC_BONG', 'KY_TUC_XA', 'LICH_HOC', 'DIEM_THI', 'TAI_KHOAN', 'CO_SO_VAT_CHAT'])->pluck('id'),
        'department_members' => DB::table('department_members')->whereIn('user_id', DB::table('users')->where('email', 'like', 'staff%@demo.local')->select('id'))->pluck('id')] as $table => $rows) {
        DB::table($table)->whereIn('id', $rows)->update(['created_at' => DB::raw('DATE_ADD(created_at, INTERVAL 7 HOUR)'), 'updated_at' => DB::raw('DATE_ADD(updated_at, INTERVAL 7 HOUR)')]);
    }
    foreach (Ticket::whereIn('id', $ids)->get() as $t) {
        $clock = $t->created_at->copy();
        $history = $t->statusHistory()->orderBy('id')->get();
        $messages = $t->messages()->orderBy('id')->get();
        $assignments = $t->assignments()->orderBy('id')->get();
        $hi = 0;
        $mi = 0;
        $ai = 0;
        foreach (DB::table('audit_logs')->where('subject_type', 'Ticket')->where('subject_id', $t->id)->orderBy('id')->get() as $audit) {
            $action = substr($audit->action, 7);
            if ($action === 'submit') {
                $clock = $t->submitted_at->copy();
            } elseif (! in_array($action, ['create', 'update', 'internal-note', 'rate'], true)) {
                $clock->addMinutes(10);
            }
            $stamp = $clock->format('Y-m-d H:i:s');
            DB::table('audit_logs')->where('id', $audit->id)->update(['created_at' => $stamp]);
            if (in_array($action, ['create', 'submit', 'assign', 'transfer', 'start', 'request-information', 'resolve', 'reopen', 'close', 'reject', 'cancel', 'messages'], true)) {
                DB::table('ticket_status_history')->where('id', $history[$hi++]->id)->update(['created_at' => $stamp]);
            }
            if (in_array($action, ['internal-note', 'request-information', 'resolve', 'reopen', 'messages'], true)) {
                DB::table('ticket_messages')->where('id', $messages[$mi++]->id)->update(['created_at' => $stamp]);
            }
            if (in_array($action, ['assign', 'transfer'], true)) {
                DB::table('ticket_assignments')->where('id', $assignments[$ai++]->id)->update(['created_at' => $stamp]);
            }
            if ($action === 'rate') {
                DB::table('ticket_ratings')->where('ticket_id', $t->id)->update(['created_at' => $stamp]);
            }
            DB::table('notifications')->where('ticket_id', $t->id)->where('event_type', $action)->update(['created_at' => $stamp, 'read_at' => DB::raw("CASE WHEN read_at IS NULL THEN NULL ELSE DATE_ADD('$stamp', INTERVAL 5 MINUTE) END")]);
        }
        foreach ($t->attachments()->with('message')->get() as $a) {
            DB::table('attachments')->where('id', $a->id)->update(['created_at' => ($a->message?->created_at ?? $t->created_at)->format('Y-m-d H:i:s')]);
        }
    }
    $metadata = $seed->metadata;
    $metadata['timeline_version'] = 2;
    DB::table('audit_logs')->where('id', $seed->id)->update(['metadata' => json_encode($metadata), 'created_at' => Carbon::parse($metadata['anchor_utc'])->format('Y-m-d H:i:s')]);
    AuditLog::create(['action' => 'demo.timeline-repair', 'metadata' => ['scope' => 'seed UUID prefix only', 'previous_mysql_timezone' => '+07:00', 'new_mysql_timezone' => '+00:00']]);
});
echo "Đã sửa timeline của bộ seed cũ sang UTC. Chạy demo:verify.\n";

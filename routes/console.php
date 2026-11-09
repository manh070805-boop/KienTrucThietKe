<?php

use App\Domain\Tickets\Workflow;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Services\ReportingService;
use Database\Seeders\FeedbackSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Artisan::command('demo:verify', function () {
    $q = Ticket::where('client_request_id', 'like', FeedbackSeeder::PREFIX.'%');
    $data = app(ReportingService::class)->summarize($q);
    $actual = (clone $q)->select('status', DB::raw('COUNT(*) AS total'))->groupBy('status')->pluck('total', 'status')->all();
    $expected = ['DRAFT' => 8, 'SUBMITTED' => 12, 'ASSIGNED' => 12, 'IN_PROGRESS' => 14, 'WAITING_STUDENT' => 12, 'RESOLVED' => 12, 'CLOSED' => 16, 'REJECTED' => 7, 'CANCELLED' => 7];
    foreach ($expected as $s => $n) {
        if (($actual[$s] ?? 0) !== $n) {
            $this->error("Sai số lượng $s");

            return 1;
        }
    }
    $anchor = AuditLog::where('action', 'demo.seed')->latest('id')->first()?->metadata['anchor_utc'];
    $snapshot = (clone $q)->whereIn('status', Workflow::OPEN)->where('due_at', '<', $anchor)->count();
    $result = ['anchor_utc' => $anchor, 'tables' => [], 'statuses' => $actual, 'report_now' => $data, 'overdue_at_seed' => $snapshot, 'reopened' => DB::table('ticket_status_history')->whereIn('ticket_id', (clone $q)->select('id'))->where('from_status', 'RESOLVED')->where('to_status', 'IN_PROGRESS')->count(), 'transfers' => DB::table('ticket_assignments')->whereIn('ticket_id', (clone $q)->select('id'))->whereNotNull('from_staff_id')->count()];
    foreach (['users', 'roles', 'user_roles', 'departments', 'department_members', 'categories', 'tickets', 'ticket_messages', 'attachments', 'ticket_assignments', 'ticket_status_history', 'notifications', 'ticket_ratings', 'audit_logs'] as $table) {
        $result['tables'][$table] = DB::table($table)->count();
    }
    $errors = [];
    $edges = array_values(Workflow::TRANSITIONS);
    $edges[] = ['WAITING_STUDENT', 'IN_PROGRESS'];
    $edges[] = ['ASSIGNED', 'ASSIGNED'];
    $edges[] = ['IN_PROGRESS', 'ASSIGNED'];
    foreach ((clone $q)->with(['statusHistory' => fn ($h) => $h->orderBy('id'), 'messages', 'attachments'])->get() as $ticket) {
        $state = null;
        $previous = $ticket->created_at;
        foreach ($ticket->statusHistory as $index => $history) {
            $from = $history->from_status?->value;
            $to = $history->to_status->value;
            if ($from !== $state || ($index === 0 ? $to !== 'DRAFT' : ! in_array([$from, $to], $edges, true)) || $history->created_at->lt($previous)) {
                $errors[] = 'Chuỗi lịch sử/timestamp sai: '.$ticket->id;
            }
            $state = $to;
            $previous = $history->created_at;
        }
        if ($state !== $ticket->status->value || $previous->gt($ticket->updated_at)) {
            $errors[] = 'Trạng thái cuối/timestamp sai: '.$ticket->id;
        }
        if ($ticket->submitted_at && ! $ticket->due_at->equalTo($ticket->submitted_at->copy()->addDays(7))) {
            $errors[] = 'SLA sai: '.$ticket->id;
        }
        foreach ($ticket->messages as $message) {
            if ($message->created_at->lt($ticket->created_at) || $message->created_at->gt($ticket->updated_at)) {
                $errors[] = 'Timestamp tin nhắn sai: '.$message->id;
            }
        }
        foreach ($ticket->attachments as $file) {
            if (! Storage::disk('local')->exists($file->storage_key) || Storage::disk('local')->size($file->storage_key) !== $file->size_bytes) {
                $errors[] = 'Tệp/metadata sai: '.$file->id;
            } elseif ((new finfo(FILEINFO_MIME_TYPE))->buffer(Storage::disk('local')->get($file->storage_key)) !== $file->mime_type) {
                $errors[] = 'MIME sai: '.$file->id;
            }
            if (! $file->message_id && $ticket->submitted_at && $file->created_at->gt($ticket->submitted_at)) {
                $errors[] = 'Tệp gốc sau thời điểm gửi: '.$file->id;
            }
        }
    }
    $distribution = DB::table('ticket_ratings')->whereIn('ticket_id', (clone $q)->select('id'))->select('score', DB::raw('COUNT(*) total'))->groupBy('score')->orderBy('score')->pluck('total', 'score')->all();
    if ($distribution !== [1 => 1, 2 => 1, 3 => 2, 4 => 4, 5 => 4]) {
        $errors[] = 'Phân bố đánh giá sai.';
    }
    $result['integrity_errors'] = $errors;
    $result['rating_distribution'] = $distribution;
    $ok = $data['total_submitted'] === 92 && $snapshot === 10 && $data['rating']['total'] === 12 && $data['rating']['average'] === 3.75 && $result['reopened'] >= 6 && $result['transfers'] >= 3 && $errors === [];
    $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(base_path('docs/demo-statistics.json'), $json."\n");
    $this->line($json);
    if (! $ok) {
        $this->error('Dataset không khớp thống kê.');

        return 1;
    }
    $this->info('Dataset khớp số lượng/trạng thái/đánh giá và mốc quá hạn đã chốt.');

    return 0;
})->purpose('Đối chiếu dataset demo, không thay đổi hồ sơ.');
Artisan::command('files:cleanup {--delete : Xóa tệp mồ côi trên 24 giờ; mặc định chỉ liệt kê}', function () {
    foreach (Storage::disk('local')->allFiles('tickets') as $key) {
        if (Storage::disk('local')->lastModified($key) < time() - 86400 && ! Attachment::where('storage_key', $key)->exists()) {
            $this->line($key);
            if ($this->option('delete')) {
                Storage::disk('local')->delete($key);
            }
        }
    }
})->purpose('Dọn tệp mồ côi sau lỗi filesystem hoặc tiến trình bị dừng.');

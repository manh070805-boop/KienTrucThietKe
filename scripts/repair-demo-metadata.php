<?php

use App\Models\Attachment;
use App\Models\Notification;
use App\Models\Ticket;
use Database\Seeders\FeedbackSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// One-time, narrowly scoped repair for the demo generated before metadata timestamps were aligned.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local')) {
    throw new RuntimeException('Chỉ dùng local.');
}
DB::transaction(function () {
    $ids = Ticket::where('client_request_id', 'like', FeedbackSeeder::PREFIX.'%')->pluck('id');
    foreach (Attachment::whereIn('ticket_id', $ids)->where('storage_key', 'like', '%/demo-%')->with(['ticket', 'message'])->get() as $a) {
        $a->forceFill(['created_at' => $a->message?->created_at ?? $a->ticket->created_at])->save();
    }
    foreach (Notification::whereIn('ticket_id', $ids)->where('event_type', 'submit')->with('ticket')->get() as $n) {
        $n->forceFill(['title' => 'Hồ sơ '.$n->ticket->code.' có cập nhật'])->save();
    }
});
echo "Đã căn chỉnh timestamp tệp và mã thông báo của dataset demo thuộc quyền seed.\n";

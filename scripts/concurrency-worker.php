<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\TicketService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'student_feedback_test', 'database.connections.mysql.port' => '3307', 'cache.default' => 'array']);
config(['filesystems.disks.local.root' => storage_path('framework/testing/private')]);
DB::purge('mysql');
[$script,$ticketId,$userId,$version,$action,$barrier,$slot] = $argv;
file_put_contents($barrier.'.'.$slot.'.ready', 'ready');
try {
    $t = Ticket::findOrFail($ticketId);
    $u = User::findOrFail($userId);
    if ($action === 'upload') {
        $result = app(AttachmentService::class)->upload($u, $t, UploadedFile::fake()->image('concurrent-'.$slot.'.png'), (int) $argv[7], (int) $version);
        echo json_encode(['status' => 201, 'id' => $result->id]);
    } else {
        $result = app(TicketService::class)->execute($u, $t, $action, ['version' => (int) $version]);
        echo json_encode(['status' => 200, 'version' => $result->version]);
    }
} catch (HttpExceptionInterface $e) {
    echo json_encode(['status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}

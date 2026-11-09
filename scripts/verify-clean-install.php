<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local') || DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('Chỉ chạy local MySQL.');
}
$name = 'student_feedback_installcheck_'.gmdate('Ymd_His');
DB::statement("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
config(['database.connections.mysql.database' => $name, 'filesystems.disks.local.root' => storage_path('app/installcheck/'.$name)]);
DB::purge('mysql');
$result = ['database' => $name, 'mysql' => DB::selectOne('SELECT VERSION() v')->v, 'timezone' => DB::selectOne('SELECT @@session.time_zone z')->z, 'steps' => []];
foreach ([['migrate', ['--force' => true]], ['db:seed', ['--force' => true]], ['demo:verify', []], ['db:seed', ['--force' => true]]] as [$command,$args]) {
    $status = Artisan::call($command, $args);
    $output = Artisan::output();
    echo "$command: $status\n$output\n";
    $result['steps'][] = ['command' => $command, 'exit_code' => $status, 'output' => $output];
    if ($status !== 0) {
        throw new RuntimeException("Thất bại: $command");
    }
}
$result['tickets'] = DB::table('tickets')->count();
$result['rating_average'] = (float) DB::table('ticket_ratings')->avg('score');
$result['verified_at_utc'] = gmdate('c');
file_put_contents(base_path('docs/clean-install-check.json'), json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
echo "Giữ database $name để đối chiếu; không reset/xóa database nào.\n";

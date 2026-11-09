<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
$connection['database'] = 'student_feedback_test';
config(['database.connections.test_files' => $connection]);
$targetRoot = storage_path('app/e2e-private');
$count = 0;
foreach (\Illuminate\Support\Facades\DB::connection('test_files')->table('attachments')->pluck('storage_key') as $key) {
    if (!preg_match('~^tickets/[0-9]+/[a-zA-Z0-9_.-]+$~', $key)) {
        throw new RuntimeException('Storage key ngoài phạm vi: '.$key);
    }
    $source = storage_path('app/private/'.$key);
    $target = $targetRoot.'/'.$key;
    if (!is_file($target) && is_file($source)) {
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0777, true);
        if (!copy($source, $target)) throw new RuntimeException('Không sao chép được tệp test.');
        $count++;
    }
}
echo "Đã sao chép $count tệp có metadata test sang disk E2E riêng, giữ nguyên nguồn.\n";

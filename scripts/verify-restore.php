<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$restoreName = 'student_feedback_restore_final_20261028';
$connection = config('database.connections.mysql');
$connection['database'] = $restoreName;
config(['database.connections.restore_check' => $connection]);
$copy = DB::connection('restore_check');
$tables = ['users','roles','user_roles','departments','department_members','categories','tickets','ticket_messages','attachments','ticket_assignments','ticket_status_history','notifications','ticket_ratings','audit_logs'];
$result = ['source_database'=>DB::connection()->getDatabaseName(),'restore_database'=>$restoreName,'verified_at_utc'=>gmdate('c'),'tables'=>[],'files'=>[]];
foreach ($tables as $table) {
    $source = DB::table($table)->count();$target = $copy->table($table)->count();
    if ($source !== $target) throw new RuntimeException("Khác số lượng $table");
    $result['tables'][$table] = $source;
}
foreach (['tickets','ticket_messages','ticket_status_history'] as $table) {
    $source = DB::table($table)->orderBy('id')->get()->all();
    $target = $copy->table($table)->orderBy('id')->get()->all();
    if (json_encode($source) !== json_encode($target)) throw new RuntimeException("Khác dữ liệu hoặc timestamp $table");
}
foreach (DB::table('attachments')->get() as $file) {
    $bytes = Storage::disk('local')->get($file->storage_key);
    if (strlen($bytes)!==$file->size_bytes) throw new RuntimeException('Metadata tệp không khớp.');
    $target=storage_path('app/backups/private-final/'.$file->storage_key);
    if(!is_dir(dirname($target)))mkdir(dirname($target),0777,true);
    file_put_contents($target,$bytes);
    $result['files'][]=['storage_key'=>$file->storage_key,'size_bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)];
}
$result['foreign_key_checks']=(int)$copy->selectOne('SELECT @@foreign_key_checks AS value')->value;
$result['rating_average']=(float)$copy->table('ticket_ratings')->avg('score');
$result['timestamps_and_rows_match']=true;
file_put_contents(base_path('docs/restore-check.json'),json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";

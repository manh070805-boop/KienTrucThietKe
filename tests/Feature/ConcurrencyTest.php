<?php

namespace Tests\Feature;

use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\FeedbackTestCase;

final class ConcurrencyTest extends FeedbackTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/private')]);
        Storage::forgetDisk('local');
    }

    public function test_two_mysql_processes_cannot_silently_overwrite_same_version(): void
    {
        $t = $this->advance('ASSIGNED');
        $version = $t->version;
        $history = $t->statusHistory()->count();
        $users = [$this->student->id, $this->other->id, $this->coordinator->id, $this->staff->id, $this->secondStaff->id, $this->admin->id];
        $departments = [$this->department->id, $this->secondDepartment->id];
        $category = $this->category->id;
        // Child connections need committed fixtures. They exist only in the isolated test DB.
        DB::commit();
        $barrier = storage_path('framework/testing/concurrency-'.bin2hex(random_bytes(6)));
        if (! is_dir(dirname($barrier))) {
            mkdir(dirname($barrier), 0777, true);
        }
        DB::beginTransaction();
        DB::table('tickets')->where('id', $t->id)->lockForUpdate()->first();
        $processes = [];
        try {
            foreach ([1, 2] as $i) {
                $p = new Process([PHP_BINARY, base_path('scripts/concurrency-worker.php'), (string) $t->id, (string) $this->staff->id, (string) $version, 'start', $barrier, (string) $i], base_path());
                $p->start();
                $processes[] = $p;
            }
            $deadline = microtime(true) + 15;
            while ((! file_exists($barrier.'.1.ready') || ! file_exists($barrier.'.2.ready')) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'.1.ready');
            $this->assertFileExists($barrier.'.2.ready');
            usleep(200000);
            DB::commit();
            $codes = [];
            foreach ($processes as $p) {
                $p->wait();
                $this->assertTrue($p->isSuccessful(), $p->getErrorOutput());
                $codes[] = json_decode($p->getOutput(), true, 512, JSON_THROW_ON_ERROR)['status'];
            }
            sort($codes);
            $this->assertSame([200, 409], $codes);
            $this->assertSame($version + 1, $t->fresh()->version);
            $this->assertSame($history + 1, $t->statusHistory()->count());
            // Two independent uploads compete for the third and last slot on one message.
            foreach ([1, 2] as $i) {
                unlink($barrier.'.'.$i.'.ready');
            }
            $t = $this->act($this->staff, $t, 'internal-note', ['body' => 'Kiểm tra giới hạn upload đồng thời.']);
            $messageId = $t->messages()->firstOrFail()->id;
            foreach ([1, 2] as $i) {
                app(AttachmentService::class)->upload($this->staff, $t, UploadedFile::fake()->image('initial-'.$i.'.png'), $messageId, $t->fresh()->version);
            }
            $uploadVersion = $t->fresh()->version;
            $uploads = [];
            DB::beginTransaction();
            DB::table('tickets')->where('id', $t->id)->lockForUpdate()->first();
            foreach ([1, 2] as $i) {
                $p = new Process([PHP_BINARY, base_path('scripts/concurrency-worker.php'), (string) $t->id, (string) $this->staff->id, (string) $uploadVersion, 'upload', $barrier, (string) $i, (string) $messageId], base_path());
                $p->start();
                $uploads[] = $p;
                $processes[] = $p;
            }
            $deadline = microtime(true) + 15;
            while ((! file_exists($barrier.'.1.ready') || ! file_exists($barrier.'.2.ready')) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'.1.ready');
            $this->assertFileExists($barrier.'.2.ready');
            DB::commit();
            $codes = [];
            foreach ($uploads as $p) {
                $p->wait();
                $this->assertTrue($p->isSuccessful(), $p->getErrorOutput());
                $codes[] = json_decode($p->getOutput(), true, 512, JSON_THROW_ON_ERROR)['status'];
            }
            sort($codes);
            $this->assertSame([201, 409], $codes);
            $this->assertSame(3, $t->attachments()->where('message_id', $messageId)->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }
            foreach ([1, 2] as $i) {
                if (file_exists($barrier.'.'.$i.'.ready')) {
                    unlink($barrier.'.'.$i.'.ready');
                }
            }
            Storage::disk('local')->deleteDirectory('tickets/'.$t->id);
            // Remove precisely the committed test fixture, without disabling FKs or resetting any database.
            foreach (['attachments', 'ticket_ratings', 'notifications', 'ticket_status_history', 'ticket_assignments', 'ticket_messages'] as $table) {
                DB::table($table)->where('ticket_id', $t->id)->delete();
            }
            DB::table('audit_logs')->where('subject_type', 'Ticket')->where('subject_id', $t->id)->delete();
            DB::table('tickets')->where('id', $t->id)->delete();
            DB::table('categories')->where('id', $category)->delete();
            DB::table('department_members')->whereIn('department_id', $departments)->delete();
            DB::table('departments')->whereIn('id', $departments)->delete();
            DB::table('user_roles')->whereIn('user_id', $users)->delete();
            DB::table('users')->whereIn('id',$users)->delete();
        }
    }
}

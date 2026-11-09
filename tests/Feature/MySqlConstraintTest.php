<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\FeedbackTestCase;

final class MySqlConstraintTest extends FeedbackTestCase
{
    private function rejects(callable $operation): void
    {
        DB::beginTransaction();
        try {
            $operation();
            $this->fail('MySQL cần từ chối dữ liệu không hợp lệ.');
        } catch (QueryException $e) {
            $this->assertContains($e->errorInfo[1], [3819, 1452, 1062, 1451]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_mysql_84_foreign_unique_and_check_constraints(): void
    {
        $this->assertStringStartsWith('8.4.', DB::selectOne('SELECT VERSION() AS v')->v);
        $t = $this->advance('CLOSED');
        $this->rejects(fn () => DB::table('tickets')->where('id', $t->id)->update(['assigned_department_id' => null, 'assigned_staff_id' => null]));
        $this->rejects(fn () => DB::table('tickets')->where('id', $t->id)->update(['assigned_staff_id' => $this->secondStaff->id]));
        $this->rejects(fn () => DB::table('tickets')->where('id', $t->id)->update(['type' => 'INVALID']));
        $this->rejects(fn () => DB::table('tickets')->where('id', $t->id)->update(['status' => 'INVALID']));
        $this->rejects(fn () => DB::table('categories')->where('id', $this->category->id)->update(['resolution_days' => 0]));
        $this->rejects(fn () => DB::table('ticket_ratings')->insert(['ticket_id' => $t->id, 'score' => 6]));
        DB::table('ticket_ratings')->insert(['ticket_id' => $t->id, 'score' => 5]);
        $this->rejects(fn () => DB::table('ticket_ratings')->insert(['ticket_id' => $t->id, 'score' => 4]));
        $this->rejects(fn () => DB::table('users')->where('id', $this->student->id)->delete());
        $this->rejects(fn () => DB::table('tickets')->insert(['student_id' => $t->student_id, 'client_request_id' => $t->client_request_id, 'type' => 'FEEDBACK', 'status' => 'DRAFT']));
        $this->rejects(fn () => DB::table('users')->insert(['name' => 'X', 'full_name' => 'X', 'email' => $this->student->email, 'password' => 'hash']));
        $this->rejects(fn () => DB::table('ticket_messages')->insert(['ticket_id' => $t->id, 'author_id' => $this->staff->id, 'kind' => 'BAD', 'visibility' => 'STUDENT_VISIBLE', 'body' => 'x']));
        $b = $this->advance();
        $b = $this->act($this->staff, $b, 'internal-note', ['body' => 'Nội bộ']);
        $message = $b->messages()->firstOrFail();
        $payload = ['ticket_id' => $t->id, 'message_id' => $message->id, 'uploaded_by' => $this->student->id, 'original_name' => 'a.png', 'storage_key' => 'x', 'mime_type' => 'image/png', 'size_bytes' => 20];
        $this->rejects(fn () => DB::table('attachments')->insert($payload));
        $payload['message_id'] = null;
        $payload['size_bytes'] = 5242881;
        $this->rejects(fn () => DB::table('attachments')->insert($payload));
        $payload['size_bytes'] = 20;
        $payload['mime_type'] = 'application/x-php';
        $this->rejects(fn () => DB::table('attachments')->insert($payload));
        $indexes = DB::select("SHOW INDEX FROM ticket_messages WHERE Key_name='ticket_messages_id_ticket_unique'");
        $this->assertCount(2, $indexes);
        $this->assertSame(0, (int) $indexes[0]->Non_unique);
    }
}

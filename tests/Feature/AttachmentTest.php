<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeedbackTestCase;

final class AttachmentTest extends FeedbackTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_private_files_owner_other_admin_and_coordinator_drafts(): void
    {
        $t = $this->draft();
        $r = $this->actingAs($this->student)->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->version, 'file' => UploadedFile::fake()->image('proof.png')], ['Accept' => 'application/json'])->assertCreated();
        $id = $r->json('data.id');
        foreach ([$this->other, $this->coordinator, $this->admin, $this->staff] as $u) {
            $this->actingAs($u)->get('/api/attachments/'.$id.'/download', ['Accept' => 'application/json'])->assertForbidden();
        }
        $this->actingAs($this->student)->get('/api/attachments/'.$id.'/download')->assertOk();
        $this->deleteJson('/api/attachments/'.$id, ['version' => $t->fresh()->version])->assertOk();
        $this->assertDatabaseMissing('attachments', ['id' => $id]);
    }

    public function test_internal_files_follow_message_visibility_and_current_assignment(): void
    {
        $t = $this->advance();
        $t = $this->act($this->staff, $t, 'internal-note', ['body' => 'Không công khai.']);
        $m = $t->messages()->where('visibility', 'STAFF_ONLY')->firstOrFail();
        $r = $this->actingAs($this->staff)->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->version, 'message_id' => $m->id, 'file' => UploadedFile::fake()->image('internal.jpg')], ['Accept' => 'application/json'])->assertCreated();
        $id = $r->json('data.id');
        $this->actingAs($this->student)->getJson('/api/attachments/'.$id.'/download')->assertForbidden();
        $this->getJson('/api/tickets/'.$t->id)->assertDontSee('internal.jpg');
        $this->actingAs($this->coordinator)->get('/api/attachments/'.$id.'/download')->assertOk();
        $this->postAction($this->coordinator, $t, 'transfer', ['department_id' => $this->secondDepartment->id, 'staff_id' => $this->secondStaff->id, 'reason' => 'Chuyển xử lý'])->assertOk();
        $this->actingAs($this->staff)->getJson('/api/attachments/'.$id.'/download')->assertForbidden();
        $this->actingAs($this->secondStaff)->get('/api/attachments/'.$id.'/download')->assertOk();
    }

    public function test_count_size_content_and_original_immutability(): void
    {
        $t = $this->draft();
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->student)->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->fresh()->version, 'file' => UploadedFile::fake()->image("p$i.png")], ['Accept' => 'application/json'])->assertCreated();
        }
        $this->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->fresh()->version, 'file' => UploadedFile::fake()->image('extra.png')], ['Accept' => 'application/json'])->assertUnprocessable();
        $t = $this->draft();
        $this->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->version, 'file' => UploadedFile::fake()->createWithContent('fake.pdf', 'not a real PDF')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->version, 'file' => UploadedFile::fake()->image('huge.png')->size(5121)], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->postAction($this->student, $t, 'submit')->assertOk();
        $this->post('/api/tickets/'.$t->id.'/attachments', ['version' => $t->fresh()->version, 'file' => UploadedFile::fake()->image('late.png')], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_upload_metadata_failure_cleans_written_file(): void
    {
        $t = $this->draft();
        $version = $t->version;
        Attachment::creating(fn () => throw new \RuntimeException('metadata failure'));
        try {
            app(AttachmentService::class)->upload($this->student, $t, UploadedFile::fake()->image('p.png'), null, $version);
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertSame('metadata failure', $e->getMessage());
        } finally {
            Attachment::flushEventListeners();
        }
        $this->assertSame([], Storage::disk('local')->allFiles('tickets'));
        $this->assertSame($version, $t->fresh()->version);
        $this->assertSame(0, Attachment::where('ticket_id', $t->id)->count());
    }

    public function test_message_from_another_ticket_is_rejected(): void
    {
        $a = $this->advance();
        $b = $this->advance();
        $b = $this->act($this->staff, $b, 'internal-note', ['body' => 'Ghi chú B']);
        $m = $b->messages()->firstOrFail();
        $this->actingAs($this->staff)->post('/api/tickets/'.$a->id.'/attachments', ['version' => $a->version, 'message_id' => $m->id, 'file' => UploadedFile::fake()->image('cross.png')], ['Accept' => 'application/json'])->assertNotFound();
    }
}

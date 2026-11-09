<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Support\Access;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class AttachmentService
{
    public function __construct(private TicketService $tickets) {}

    public function upload(User $user, Ticket $ticket, UploadedFile $file, ?int $messageId, int $version): Attachment
    {
        $mime = $file->getMimeType();
        $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (! isset($extensions[$mime]) || $file->getSize() <= 0 || $file->getSize() > 5242880) {
            throw ValidationException::withMessages(['file' => ['Chỉ nhận PDF/JPG/PNG thật, dung lượng tối đa 5 MB.']]);
        }
        $bytes = file_get_contents($file->getRealPath());
        if (($mime === 'application/pdf' && (! str_starts_with($bytes, '%PDF-') || ! str_contains(substr($bytes, -2048), '%%EOF')))
            || ($mime !== 'application/pdf' && @getimagesize($file->getRealPath()) === false)) {
            throw ValidationException::withMessages(['file' => ['Nội dung tệp không hợp lệ.']]);
        }
        $key = 'tickets/'.$ticket->id.'/'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        try {
            return DB::transaction(function () use ($user, $ticket, $file, $messageId, $version, $mime, $key, $bytes) {
                $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
                abort_unless(Access::canViewTicket($user, $locked), 403);
                $this->tickets->assertVersion($locked, $version);
                if ($messageId) {
                    $message = TicketMessage::whereKey($messageId)->where('ticket_id', $locked->id)->firstOrFail();
                    abort_unless((int) $message->author_id === (int) $user->id && (! ($message->visibility->value === 'STAFF_ONLY') || Access::canSeeInternal($user, $locked)), 403);
                    abort_if(in_array($locked->status->value, ['CLOSED', 'CANCELLED', 'REJECTED'], true), 409);
                } else {
                    abort_unless(Access::owner($user, $locked) && $locked->status->value === 'DRAFT', 403, 'Minh chứng gốc chỉ được tải lên khi còn nháp.');
                }
                if ($locked->attachments()->where('message_id', $messageId)->count() >= 3) {
                    throw ValidationException::withMessages(['file' => ['Mỗi nội dung gốc hoặc tin nhắn chỉ có tối đa 3 tệp.']]);
                }
                if (! Storage::disk('local')->put($key, $bytes)) {
                    throw new \RuntimeException('Không ghi được tệp.');
                }
                $attachment = Attachment::create(['ticket_id' => $locked->id, 'message_id' => $messageId, 'uploaded_by' => $user->id,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'storage_key' => $key, 'mime_type' => $mime, 'size_bytes' => $file->getSize()]);
                $locked->version++;
                $locked->save();

                return $attachment;
            }); // Do not automatically retry a transaction containing filesystem writes.
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($key);
            throw $e;
        }
    }

    public function canDownload(User $user, Attachment $attachment): bool
    {
        return Access::canViewTicket($user, $attachment->ticket)
            && (! $attachment->message || $attachment->message->visibility->value !== 'STAFF_ONLY' || Access::canSeeInternal($user, $attachment->ticket));
    }

    public function delete(User $user, Attachment $attachment, int $version): void
    {
        $key = DB::transaction(function () use ($user, $attachment, $version) {
            $t = Ticket::whereKey($attachment->ticket_id)->lockForUpdate()->firstOrFail();
            abort_unless(Access::owner($user, $t) && $t->status->value === 'DRAFT' && ! $attachment->message_id, 403);
            $this->tickets->assertVersion($t, $version);
            $key = $attachment->storage_key;
            $attachment->delete();
            $t->version++;
            $t->save();

            return $key;
        });
        // If deletion fails, files:cleanup can remove the unreferenced file after its grace period.
        Storage::disk('local')->delete($key);
    }
}

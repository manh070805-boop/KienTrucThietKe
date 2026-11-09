<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class AttachmentController extends Controller
{
    public function store(Request $request, Ticket $ticket, AttachmentService $service)
    {
        $d = $request->validate(['file' => ['required', 'file', 'max:5120'], 'message_id' => ['nullable', 'integer'], 'version' => ['required', 'integer', 'min:1']]);
        $a = $service->upload($request->user(), $ticket, $request->file('file'), isset($d['message_id']) ? (int) $d['message_id'] : null, (int) $d['version']);

        return response()->json(['data' => ['id' => $a->id, 'original_name' => $a->original_name, 'version' => $ticket->fresh()->version]], 201);
    }

    public function download(Request $request, Attachment $attachment, AttachmentService $service)
    {
        abort_unless($service->canDownload($request->user(), $attachment), 403);
        abort_unless(Storage::disk('local')->exists($attachment->storage_key), 404, 'Tệp không còn tồn tại.');

        return Storage::disk('local')->download($attachment->storage_key, $attachment->original_name, ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function destroy(Request $request, Attachment $attachment, AttachmentService $service)
    {
        $d = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $service->delete($request->user(), $attachment, (int) $d['version']);

        return response()->json(['message' => 'Đã xóa minh chứng nháp.']);
    }
}

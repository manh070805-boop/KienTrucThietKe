<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\Access;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    private function query(Request $r)
    {
        return Notification::where('user_id', $r->user()->id)->where(fn ($q) => $q->whereNull('ticket_id')
            ->orWhereHas('ticket', fn ($t) => Access::scope($t, $r->user())))->where('event_type', '!=', 'internal-note');
    }

    public function index(Request $r)
    {
        $r->validate(['per_page' => ['nullable', 'integer', 'between:1,50']]);
        $query = $this->query($r);
        $p = $query->latest('created_at')->orderByDesc('id')->paginate($r->integer('per_page', 20));
        $p->getCollection()->transform(fn ($n) => $n->only(['id', 'ticket_id', 'title', 'body', 'event_type', 'read_at', 'created_at']));

        return response()->json(['data' => $p->items(), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total(), 'unread' => $this->query($r)->whereNull('read_at')->count()]]);
    }

    public function read(Request $r, Notification $notification)
    {
        abort_unless($this->query($r)->whereKey($notification->id)->exists(), 403);
        $notification->forceFill(['read_at' => now()])->save();

        return response()->json(['message' => 'Đã đánh dấu đã đọc.']);
    }
}

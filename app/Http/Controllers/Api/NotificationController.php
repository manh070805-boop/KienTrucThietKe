<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request) { return response()->json(Notification::where('user_id', $request->user()->id)->latest('created_at')->paginate(20)); }
    public function read(Request $request, Notification $notification) { abort_unless($notification->user_id === $request->user()->id, 403); $notification->update(['read_at' => now()]); return response()->json(['message' => 'Đã đánh dấu đã đọc.']); }
}

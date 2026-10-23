<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); $base = Ticket::query()->when(Access::role($user, 'STUDENT'), fn ($q) => $q->where('student_id', $user->id))->when(Access::role($user, 'STAFF') && !Access::role($user, 'COORDINATOR'), fn ($q) => $q->where('assigned_staff_id', $user->id))->when(Access::role($user, 'COORDINATOR'), fn ($q) => $q->where('status', '!=', 'DRAFT'));
        $counts = (clone $base)->where('status', '!=', 'DRAFT')->select('status', DB::raw('COUNT(*) total'))->groupBy('status')->pluck('total', 'status');
        $overdue = (clone $base)->whereIn('status', array_map(fn ($s) => $s->value, array_filter(TicketStatus::cases(), fn ($s) => $s->isOpen())))->where('due_at', '<', now())->count();
        $rating = DB::table('ticket_ratings')->join('tickets', 'tickets.id', '=', 'ticket_ratings.ticket_id')->whereIn('tickets.id', (clone $base)->select('id'))->selectRaw('AVG(score) average, COUNT(*) total')->first();
        return response()->json(['data' => ['total_submitted' => (clone $base)->whereNotIn('status', ['DRAFT'])->count(), 'by_status' => $counts, 'overdue_open' => $overdue, 'rating' => ['average' => $rating?->average ? round((float) $rating->average, 2) : null, 'total' => (int) ($rating?->total ?? 0)]]]);
    }
}

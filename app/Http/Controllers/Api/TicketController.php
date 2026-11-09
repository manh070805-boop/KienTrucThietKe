<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketQuery;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TicketController extends Controller
{
    public function index(Request $request, TicketQuery $query)
    {
        return TicketResource::collection($query->build($request, $request->user())->with(TicketQuery::RELATIONS)
            ->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc'))->orderByDesc('id')->paginate($request->integer('per_page', 15)));
    }

    public function store(TicketRequest $request, TicketService $service)
    {
        $ticket = $service->create($request->user(), $request->validated());

        return $this->resource($ticket);
    }

    public function show(Request $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        return $this->resource($ticket);
    }

    public function update(TicketRequest $request, Ticket $ticket, TicketService $service)
    {
        return $this->resource($service->execute($request->user(), $ticket, 'update', $request->validated()));
    }

    public function action(TicketRequest $request, Ticket $ticket, string $action, TicketService $service)
    {
        return $this->resource($service->execute($request->user(), $ticket, $action, $request->validated()));
    }

    private function resource(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket->load([...TicketQuery::RELATIONS, 'messages' => fn ($q) => $q->orderBy('created_at')->orderBy('id'), 'messages.author', 'messages.attachments',
            'attachments', 'statusHistory' => fn ($q) => $q->orderBy('created_at')->orderBy('id'), 'assignments.toDepartment', 'assignments.toStaff']));
    }
}

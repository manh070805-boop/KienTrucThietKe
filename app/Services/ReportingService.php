<?php

namespace App\Services;

use App\Domain\Tickets\Workflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ReportingService
{
    public function summarize(Builder $query): array
    {
        $base = (clone $query)->where('status', '!=', 'DRAFT');
        $group = fn ($column) => (object) (clone $base)->select($column, DB::raw('COUNT(*) AS total'))->groupBy($column)->pluck('total', $column)->all();
        $rating = DB::table('ticket_ratings')->whereIn('ticket_id', (clone $base)->select('id'))->selectRaw('AVG(score) AS average, COUNT(*) AS total')->first();

        return ['total_submitted' => (clone $base)->count(), 'by_status' => $group('status'), 'by_type' => $group('type'),
            'by_category' => $group('category_id'), 'by_department' => $group('assigned_department_id'),
            'overdue_open' => (clone $base)->whereIn('status', Workflow::OPEN)->where('due_at', '<', now())->count(),
            'rating' => ['average' => $rating->average === null ? null : round((float) $rating->average, 2), 'total' => (int) $rating->total]];
    }
}

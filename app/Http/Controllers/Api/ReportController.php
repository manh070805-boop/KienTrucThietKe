<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use App\Services\TicketQuery;
use App\Support\Access;
use Illuminate\Http\Request;

final class ReportController extends Controller
{
    public function index(Request $r, TicketQuery $query, ReportingService $reporting)
    {
        abort_unless(Access::role($r->user(), 'STUDENT', 'STAFF', 'COORDINATOR'), 403);

        return response()->json(['data' => $reporting->summarize($query->build($r, $r->user()))]);
    }

    public function csv(Request $r, TicketQuery $query)
    {
        abort_unless(Access::role($r->user(), 'STUDENT', 'STAFF', 'COORDINATOR'), 403);
        $q = $query->build($r, $r->user())->where('status', '!=', 'DRAFT')->with(TicketQuery::RELATIONS);

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mã hồ sơ', 'Tiêu đề', 'Loại', 'Trạng thái', 'Danh mục', 'Đơn vị', 'Ngày gửi', 'Hạn xử lý']);
            foreach ($q->lazyById(200) as $t) {
                $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v;
                fputcsv($out, array_map($safe, [$t->code, $t->title, $t->type->value, $t->status->value, $t->category?->name, $t->assignedDepartment?->name, $t->submitted_at?->toIso8601String(), $t->due_at?->toIso8601String()]));
            }
            fclose($out);
        }, 'bao-cao-ho-so.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}

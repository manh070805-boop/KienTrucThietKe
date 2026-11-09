<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Support\Access;
use Illuminate\Http\Request;

final class DirectoryController extends Controller
{
    public function index(Request $r)
    {
        abort_unless(Access::role($r->user(), 'STUDENT', 'COORDINATOR', 'STAFF', 'ADMIN'), 403);

        return response()->json(['data' => [
            'categories' => Category::where('is_active', true)->whereHas('department', fn ($q) => $q->where('is_active', true))->orderBy('name')->get(['id', 'code', 'name', 'default_department_id', 'resolution_days']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'staff' => Access::role($r->user(), 'COORDINATOR', 'ADMIN') ? DepartmentMember::with('user')->where('is_active', true)->whereHas('department', fn ($q) => $q->where('is_active', true))
                ->whereHas('user', fn ($q) => $q->where('is_active', true)->whereHas('roles', fn ($r) => $r->where('code', 'STAFF')))->get()
                ->map(fn ($m) => ['id' => $m->user_id, 'department_id' => $m->department_id, 'full_name' => $m->user->displayName()]) : [],
        ]]);
    }
}

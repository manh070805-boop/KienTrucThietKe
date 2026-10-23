<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Support\Access;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(Access::role($user, 'STUDENT', 'COORDINATOR', 'STAFF', 'ADMIN'), 403);
        return response()->json(['data' => [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'default_department_id', 'resolution_days']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'staff' => DepartmentMember::with('user:id,full_name,name,email')->where('is_active', true)->whereHas('user', fn ($q) => $q->where('is_active', true)->whereHas('roles', fn ($r) => $r->where('code', 'STAFF')))->get()->map(fn ($member) => ['id' => $member->user_id, 'department_id' => $member->department_id, 'full_name' => $member->user->displayName(), 'email' => $member->user->email]),
        ]]);
    }
}

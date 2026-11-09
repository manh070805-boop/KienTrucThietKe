<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\User;
use App\Services\ReportingService;
use App\Services\TicketQuery;
use App\Support\Access;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $request, TicketQuery $query, ReportingService $reporting)
    {
        $data = $reporting->summarize($query->build($request, $request->user()));
        if (Access::role($request->user(), 'ADMIN')) {
            $data['administration'] = [
                'users' => User::count(), 'departments' => Department::count(), 'members' => DepartmentMember::count(), 'categories' => Category::count(), 'roles' => Role::count()];
        }

        return response()->json(['data' => $data]);
    }
}

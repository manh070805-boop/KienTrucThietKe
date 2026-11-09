<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Services\AdminService;
use App\Support\Access;
use Illuminate\Http\Request;

final class AdminController extends Controller
{
    public function index(Request $r, string $entity)
    {
        abort_unless(Access::role($r->user(), 'ADMIN'), 403);
        $r->validate(['search' => ['nullable', 'string', 'max:255'], 'per_page' => ['nullable', 'integer', 'between:1,50']]);
        $class = AdminService::MODELS[$entity];
        $q = $class::query();
        if ($entity === 'users') {
            $q->with('roles');
        }
        if ($entity === 'members') {
            $q->with(['user', 'department']);
        }
        if ($r->filled('search')) {
            $col = match ($entity) {
                'users' => 'full_name','members' => 'id',default => 'name'
            };
            $q->where($col, 'like', '%'.$r->input('search').'%');
        }
        $p = $q->orderBy('id')->paginate($r->integer('per_page', 15));
        $p->getCollection()->transform(fn ($m) => $this->serialize($entity, $m));

        return response()->json(['data' => $p->items(), 'meta' => ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]]);
    }

    public function store(AdminRequest $r, string $entity, AdminService $service)
    {
        return response()->json(['data' => $this->serialize($entity, $service->save($r->user(), $entity, null, $r->validated()))], 201);
    }

    public function update(AdminRequest $r, string $entity, int $id, AdminService $service)
    {
        return response()->json(['data' => $this->serialize($entity, $service->save($r->user(), $entity, $id, $r->validated()))]);
    }

    private function serialize(string $entity, $m): array
    {
        return match ($entity) {
            'users' => $m->only(['id', 'full_name', 'email', 'student_code', 'is_active']) + ['roles' => $m->roles->pluck('code')],
            'members' => $m->only(['id', 'department_id', 'user_id', 'is_active']) + ['full_name' => $m->user->displayName(), 'department' => $m->department->name],
            'categories' => $m->only(['id', 'code', 'name', 'default_department_id', 'resolution_days', 'is_active']),
            'departments' => $m->only(['id', 'code', 'name', 'is_active']),
            'roles' => $m->only(['id', 'code', 'name']),
        };
    }
}

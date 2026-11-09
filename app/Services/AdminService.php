<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdminService
{
    public const MODELS = ['users' => User::class, 'roles' => Role::class, 'departments' => Department::class, 'members' => DepartmentMember::class, 'categories' => Category::class];

    public function save(User $actor, string $entity, ?int $id, array $data)
    {
        return DB::transaction(function () use ($actor, $entity, $id, $data) {
            $class = self::MODELS[$entity];
            $model = $id ? $class::whereKey($id)->lockForUpdate()->firstOrFail() : new $class;
            if ($entity === 'users') {
                if ($id === $actor->id && (! $data['is_active'] || ! in_array('ADMIN', $data['roles'], true))) {
                    throw ValidationException::withMessages(['roles' => ['Bạn không thể tự khóa hoặc gỡ quyền quản trị của chính mình.']]);
                }
                $roles = Role::whereIn('code', $data['roles'])->pluck('id');
                $payload = Arr::except($data, ['roles']);
                if (empty($payload['password'])) {
                    unset($payload['password']);
                }
                $payload['name'] = $payload['full_name'];
                if ($id && (! $data['is_active'] || ! in_array('STAFF', $data['roles'], true))) {
                    $this->ensureNoOpenAssignment('assigned_staff_id', $id);
                }
                $model->fill($payload)->save();
                $model->roles()->sync($roles);
            } elseif ($entity === 'members') {
                if ($id && ((int) $model->department_id !== (int) $data['department_id'] || (int) $model->user_id !== (int) $data['user_id'])) {
                    throw ValidationException::withMessages(['user_id' => ['Không đổi cặp thành viên đã lưu; hãy ngừng hoạt động và tạo thành viên mới.']]);
                }
                $user = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
                if ($data['is_active'] && (! $user->is_active || ! $user->hasRole('STAFF') || ! Department::findOrFail($data['department_id'])->is_active)) {
                    throw ValidationException::withMessages(['user_id' => ['Thành viên phải là cán bộ và đơn vị đang hoạt động.']]);
                }
                if (! $id && DepartmentMember::where('department_id', $data['department_id'])->where('user_id', $data['user_id'])->exists()) {
                    throw ValidationException::withMessages(['user_id' => ['Thành viên đã tồn tại. Hãy sửa trạng thái bản ghi cũ.']]);
                }
                if ($id && ! $data['is_active'] && Ticket::where('assigned_department_id', $model->department_id)->where('assigned_staff_id', $model->user_id)->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'WAITING_STUDENT', 'RESOLVED'])->exists()) {
                    throw ValidationException::withMessages(['is_active' => ['Cần chuyển hồ sơ đang xử lý trước khi ngừng thành viên.']]);
                }
                $model->fill($data)->save();
            } else {
                if ($entity === 'departments' && $id && ! $data['is_active']) {
                    $this->ensureNoOpenAssignment('assigned_department_id', $id);
                }
                if ($entity === 'categories' && $data['is_active'] && ! Department::findOrFail($data['default_department_id'])->is_active) {
                    throw ValidationException::withMessages(['default_department_id' => ['Đơn vị mặc định đã ngừng hoạt động.']]);
                }
                $model->fill($data)->save();
            }
            AuditLog::create(['actor_id' => $actor->id, 'action' => 'admin.'.$entity.'.'.($id ? 'update' : 'create'), 'subject_type' => $entity, 'subject_id' => $model->id,
                'metadata' => ['fields' => array_values(array_diff(array_keys($data), ['password']))]]);

            return $model;
        }, 3);
    }

    private function ensureNoOpenAssignment(string $column, int $id): void
    {
        if (Ticket::where($column, $id)->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'WAITING_STUDENT', 'RESOLVED'])->exists()) {
            throw ValidationException::withMessages(['is_active' => ['Cần chuyển hoặc hoàn tất các hồ sơ được giao trước khi ngừng hoạt động/gỡ STAFF.']]);
        }
    }
}

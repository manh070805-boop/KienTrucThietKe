<?php

namespace App\Http\Requests;

use App\Support\Access;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Access::role($this->user(), 'ADMIN');
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $active = ['is_active' => ['required', 'boolean']];

        return match ($this->route('entity')) {
            'users' => $active + [
                'full_name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
                'student_code' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($id)],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:12', 'max:128'],
                'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['required', Rule::in(['STUDENT', 'STAFF', 'COORDINATOR', 'ADMIN'])],
            ],
            'roles' => ['name' => ['required', 'string', 'max:100']],
            'departments' => $active + ['code' => ['required', 'alpha_dash', 'max:30', Rule::unique('departments')->ignore($id)], 'name' => ['required', 'string', 'max:150']],
            'categories' => $active + ['code' => ['required', 'alpha_dash', 'max:40', Rule::unique('categories')->ignore($id)], 'name' => ['required', 'string', 'max:150'],
                'default_department_id' => ['required', 'integer', 'exists:departments,id'], 'resolution_days' => ['required', 'integer', 'between:1,365']],
            'members' => $active + ['department_id' => ['required', 'integer', 'exists:departments,id'], 'user_id' => ['required', 'integer', 'exists:users,id']],
        };
    }
}

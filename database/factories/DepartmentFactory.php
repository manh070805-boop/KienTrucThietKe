<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('D_????##')), 'name' => 'Đơn vị thử nghiệm '.fake()->word(), 'is_active' => true];
    }
}

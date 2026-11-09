<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('C_????##')), 'name' => 'Danh mục thử nghiệm '.fake()->word(), 'default_department_id' => Department::factory(), 'resolution_days' => 7, 'is_active' => true];
    }
}

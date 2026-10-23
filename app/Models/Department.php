<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function members() { return $this->hasMany(DepartmentMember::class); }
    public function categories() { return $this->hasMany(Category::class, 'default_department_id'); }
}

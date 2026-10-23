<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentMember extends Model
{
    protected $fillable = ['department_id', 'user_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function department() { return $this->belongsTo(Department::class); }
    public function user() { return $this->belongsTo(User::class); }
}

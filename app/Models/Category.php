<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['code', 'name', 'default_department_id', 'resolution_days', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'resolution_days' => 'integer'];
    public function department() { return $this->belongsTo(Department::class, 'default_department_id'); }
    public function tickets() { return $this->hasMany(Ticket::class); }
}

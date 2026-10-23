<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['full_name', 'name', 'email', 'student_code', 'password', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }
    public function roles() { return $this->belongsToMany(Role::class, 'user_roles'); }
    public function departmentMemberships() { return $this->hasMany(DepartmentMember::class); }
    public function tickets() { return $this->hasMany(Ticket::class, 'student_id'); }
    public function hasRole(string $code): bool { return $this->roles()->where('code', $code)->exists(); }
    public function displayName(): string { return $this->full_name ?: ($this->name ?: $this->email); }
}

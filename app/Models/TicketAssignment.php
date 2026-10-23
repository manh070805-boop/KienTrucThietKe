<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'from_department_id', 'from_staff_id', 'to_department_id', 'to_staff_id', 'assigned_by', 'reason'];
    protected $casts = ['created_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function toDepartment() { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function toStaff() { return $this->belongsTo(User::class, 'to_staff_id'); }
}

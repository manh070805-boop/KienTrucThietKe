<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['client_request_id', 'type', 'title', 'description', 'category_id'];
    protected $casts = [
        'type' => TicketType::class,
        'status' => TicketStatus::class,
        'submitted_at' => 'datetime', 'due_at' => 'datetime', 'resolved_at' => 'datetime', 'closed_at' => 'datetime',
        'version' => 'integer',
    ];
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function category() { return $this->belongsTo(Category::class); }
    public function assignedDepartment() { return $this->belongsTo(Department::class, 'assigned_department_id'); }
    public function assignedStaff() { return $this->belongsTo(User::class, 'assigned_staff_id'); }
    public function messages() { return $this->hasMany(TicketMessage::class); }
    public function attachments() { return $this->hasMany(Attachment::class); }
    public function assignments() { return $this->hasMany(TicketAssignment::class); }
    public function statusHistory() { return $this->hasMany(TicketStatusHistory::class); }
    public function rating() { return $this->hasOne(TicketRating::class); }
    public function isOverdue(): bool { return $this->status?->isOpen() && $this->due_at?->isPast(); }
}

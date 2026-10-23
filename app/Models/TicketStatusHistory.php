<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    protected $table = 'ticket_status_history';
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'from_status', 'to_status', 'changed_by', 'reason'];
    protected $casts = ['from_status' => TicketStatus::class, 'to_status' => TicketStatus::class, 'created_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function changer() { return $this->belongsTo(User::class, 'changed_by'); }
}

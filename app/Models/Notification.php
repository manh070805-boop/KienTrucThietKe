<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'ticket_id', 'event_type', 'title', 'body'];
    protected $casts = ['read_at' => 'datetime', 'created_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
}

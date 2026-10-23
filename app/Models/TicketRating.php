<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketRating extends Model
{
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'score', 'comment'];
    protected $casts = ['score' => 'integer', 'created_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
}

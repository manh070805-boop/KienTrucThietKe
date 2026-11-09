<?php

namespace App\Models;

use App\Models\Concerns\RecordsCreatedAt;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use RecordsCreatedAt;

    public $timestamps = false;

    protected $fillable = ['user_id', 'ticket_id', 'event_type', 'title', 'body'];

    protected $casts = ['read_at' => 'datetime', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}

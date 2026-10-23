<?php

namespace App\Models;

use App\Enums\MessageKind;
use App\Enums\MessageVisibility;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'author_id', 'kind', 'visibility', 'body'];
    protected $casts = ['kind' => MessageKind::class, 'visibility' => MessageVisibility::class, 'created_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'message_id'); }
}

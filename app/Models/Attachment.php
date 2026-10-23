<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'message_id', 'uploaded_by', 'original_name', 'storage_key', 'mime_type', 'size_bytes'];
    protected $casts = ['created_at' => 'datetime', 'size_bytes' => 'integer'];
    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function message() { return $this->belongsTo(TicketMessage::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}

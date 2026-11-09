<?php

namespace App\Models;

use App\Models\Concerns\RecordsCreatedAt;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use RecordsCreatedAt;

    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'metadata'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

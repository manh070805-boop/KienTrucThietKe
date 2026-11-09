<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait RecordsCreatedAt
{
    public static function bootRecordsCreatedAt(): void
    {
        static::creating(function (Model $model) {
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }
}

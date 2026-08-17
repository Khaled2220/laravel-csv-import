<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
      protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'status',
        'total_records',
        'processed_records',
        'failed_records',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
     public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }
}

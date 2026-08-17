<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
      protected $fillable = [
        'import_id',
        'row_number',
        'row_data',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}

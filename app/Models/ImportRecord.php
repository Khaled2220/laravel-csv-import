<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRecord extends Model
{
    protected $fillable = [
        'import_id',
        'row_number',
        'status'
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

}

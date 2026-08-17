<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyFeature extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'value',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

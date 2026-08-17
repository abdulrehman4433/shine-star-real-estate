<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBlock extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plotSizes(): HasMany
    {
        return $this->hasMany(ProjectPlotSize::class)->orderBy('order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'assigned_to',
        'title',
        'description',
        'due_date',
        'is_completed',
        'completed_at',
        'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return ! $this->is_completed && $this->due_date->isPast();
    }

    public function scopeDueForReminder($query)
    {
        return $query->whereNull('reminded_at')
            ->where('is_completed', false)
            ->whereDate('due_date', '<=', now()->toDateString());
    }
}

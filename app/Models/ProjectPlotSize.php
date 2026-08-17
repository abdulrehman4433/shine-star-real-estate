<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPlotSize extends Model
{
    protected $fillable = [
        'project_id',
        'project_block_id',
        'size_value',
        'unit',
        'category',
        'bedrooms',
        'bathrooms',
        'total_price',
        'booking_amount',
        'confirmation_amount',
        'installment_amount',
        'installment_count',
        'installment_frequency',
        'possession_amount',
        'notes',
        'order',
    ];

    public const UNITS = [
        'marla' => 'Marla',
        'kanal' => 'Kanal',
        // For offerings that aren't raw land — built apartments/shops in a high-rise (e.g. ESMR
        // Heights) are conventionally priced per square foot, not per marla/kanal.
        'sqft' => 'Sqft',
    ];

    public const INSTALLMENT_FREQUENCIES = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'half_yearly' => 'Half Yearly',
        'yearly' => 'Yearly',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ProjectBlock::class, 'project_block_id');
    }

    public function getLabelAttribute(): string
    {
        $size = rtrim(rtrim(number_format((float) $this->size_value, 2), '0'), '.');

        return $size.' '.(self::UNITS[$this->unit] ?? $this->unit);
    }

    /** "2 Bed · 1 Bath", "2 Bed" (bathrooms not set), or null if neither is set — a plain plot of
     *  land has no bedroom/bathroom count, so this is only meaningful for villas/apartments. */
    public function getBedBathLabelAttribute(): ?string
    {
        $parts = array_filter([
            $this->bedrooms ? $this->bedrooms.' Bed' : null,
            $this->bathrooms ? $this->bathrooms.' Bath' : null,
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }
}

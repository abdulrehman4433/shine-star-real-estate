<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    protected $fillable = [
        'name',
        'status',
        'columns',
        'company_name',
        'copyright_text',
        'copyright_tagline',
        'social_links',
        'show_company_name',
        'show_tagline',
        'show_social_links',
        'show_copyright',
        'position_company_name',
        'position_tagline',
        'position_social_links',
        'position_copyright',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'integer',
            'social_links' => 'array',
            'show_company_name' => 'boolean',
            'show_tagline' => 'boolean',
            'show_social_links' => 'boolean',
            'show_copyright' => 'boolean',
        ];
    }

    /**
     * Get the currently active footer settings.
     */
    public static function current(): ?self
    {
        return static::where('status', 'active')->first() ?: static::first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

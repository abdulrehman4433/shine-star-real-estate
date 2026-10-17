<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** A block/precinct inside a housing society (e.g. "Block A"). Each block carries its own
 *  JSON plot-size options (every size option holding its own dynamic key/value payment rows),
 *  its own external video links, and its own media files (images/videos/PDFs) — see the
 *  2026-10-14 restructure and 2026-10-15 payment-relink migrations. */
class ProjectBlock extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const UNITS = [
        'marla' => 'Marla',
        'kanal' => 'Kanal',
        // For offerings that aren't raw land — built apartments/shops in a high-rise (e.g. ESMR
        // Heights) are conventionally priced per square foot, not per marla/kanal.
        'sqft' => 'Sqft',
    ];

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'plot_sizes',
        'video_urls',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'plot_sizes' => 'array',
            'video_urls' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('block_images');
        $this->addMediaCollection('block_videos');
        $this->addMediaCollection('block_pdfs');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Scoped to images only — same reason as Project: never let Spatie try to build a
        // GD/ffmpeg conversion from a video or PDF upload (fatals without the binaries).
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320)
            ->nonQueued()
            ->performOnCollections('block_images');
    }

    /** "5 Marla" / "1 Kanal" — trailing zeros trimmed (5.00 -> 5), matching the label the old
     *  ProjectPlotSize::getLabelAttribute() produced before sizes moved into this JSON column. */
    public static function sizeLabel(array $row): string
    {
        $size = rtrim(rtrim(number_format((float) ($row['size_value'] ?? 0), 2), '0'), '.');
        $unit = (string) ($row['unit'] ?? '');

        return $size.' '.(self::UNITS[$unit] ?? $unit);
    }

    /** The dynamic key/value payment rows belonging to ONE size option of this block
     *  (payment is per-size, not per-block — see the 2026-10-15 migration). */
    public static function paymentOptionsFor(array $row): array
    {
        return collect($row['payment_options'] ?? [])
            ->filter(fn ($option) => is_array($option))
            ->map(fn ($option) => [
                'key' => (string) ($option['key'] ?? ''),
                'value' => (string) ($option['value'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /** The union of every size option's payment keys for a block, ordered for the frontend
     *  payment-plan table: margin first, then down payment/booking, monthly installments,
     *  half yearly, any other terms in their natural order — and total price always last.
     *  Keys are matched loosely (admin input is free-form) but displayed as originally typed. */
    public static function paymentColumnsFor(array $sizes): array
    {
        $keys = [];

        foreach ($sizes as $row) {
            foreach (self::paymentOptionsFor($row) as $option) {
                $key = trim($option['key']);

                if ($key !== '' && ! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        // PHP 8 sorts are stable, so equal ranks keep their natural first-seen order.
        usort($keys, fn ($a, $b) => self::paymentColumnRank($a) <=> self::paymentColumnRank($b));

        return $keys;
    }

    /** Display order for one free-form payment key (lower sorts earlier). Covers the requested
     *  layout: "marging"/"margin"/"merging" first, "down payment"/"booking" second, "monthly"/
     *  "installment" third, "half yearly" fourth, other terms after those, and total-price-like
     *  keys ("total price", "total rate", …) always pinned to the last column. */
    private static function paymentColumnRank(string $key): int
    {
        $key = strtolower($key);

        return match (true) {
            str_contains($key, 'margin') || str_contains($key, 'merging') => 1,
            str_contains($key, 'down') || str_contains($key, 'booking') || str_contains($key, 'token') => 2,
            str_contains($key, 'month') || str_contains($key, 'installment') => 3,
            str_contains($key, 'half') => 4,
            str_contains($key, 'total') => 6,
            default => 5,
        };
    }

    /** Every external video link for this block (empty strings dropped). */
    public function videoLinkRows(): array
    {
        return array_values(array_filter($this->video_urls ?? [], fn ($url) => is_string($url) && trim($url) !== ''));
    }

    /** Embeddable iframe src for each video link (YouTube/Vimeo share links converted). */
    public function getVideoEmbedUrlsAttribute(): array
    {
        return array_map(fn ($url) => Project::embedUrlFor($url), $this->videoLinkRows());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageTemplate extends Model
{
    protected $fillable = [
        'name',
        'status',
        'is_active',
        'html',
        'css',
        'js',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Get the currently active page template (cached). */
    public static function activeTemplate(): ?self
    {
        return Cache::remember('page_template.active', now()->addHour(), function () {
            return static::where('is_active', true)->where('status', 'published')->first();
        });
    }

    /** Forget the active page template cache. */
    public static function forgetActiveCache(): void
    {
        Cache::forget('page_template.active');
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Parse settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        return array_merge(static::defaultSettings(), $this->settings ?? []);
    }

    public static function defaultSettings(): array
    {
        return [
            'container_width' => 1280,
            'content_font_family' => "'Inter', system-ui, -apple-system, sans-serif",
            'content_font_size' => 16,
            'content_line_height' => 1.75,
            'content_text_color' => '#374151',
            'content_bg_color' => '#ffffff',
            'heading_font_family' => "'Inter', system-ui, -apple-system, sans-serif",
            'heading_color' => '#111827',
            'heading_font_weight' => 700,
            'show_title' => true,
            'title_alignment' => 'left',
            'title_bg_color' => '#f8fafc',
            'title_padding_y' => 60,
            'border_radius' => 12,
            'shadow_enabled' => false,
            'shadow_color' => 'rgba(0,0,0,0.08)',
            'enable_animation' => true,
            'animation_style' => 'fade-up',
            'mobile_breakpoint' => 768,
        ];
    }

    /**
     * Render the stored HTML template with page-specific data.
     */
    public function renderHtml(array $pageData = []): string
    {
        if (empty($this->html)) {
            return '';
        }

        $settings = $this->getSettingsWithDefaults();

        $data = array_merge([
            'pageTitle' => $pageData['title'] ?? '',
            'pageSlug' => $pageData['slug'] ?? '',
            'pageMetaTitle' => $pageData['meta_title'] ?? '',
            'pageMetaDescription' => $pageData['meta_description'] ?? '',
            'containerWidth' => $settings['container_width'].'px',
            'titleAlignment' => $settings['title_alignment'],
            'showTitle' => $settings['show_title'],
            'enableAnimation' => $settings['enable_animation'],
            'animationStyle' => $settings['animation_style'],
        ], $pageData);

        return $this->safeSubstitute($this->html, $data);
    }

    /**
     * Render the stored CSS template with settings applied.
     */
    public function renderCss(): string
    {
        if (empty($this->css)) {
            return '';
        }

        $s = $this->getSettingsWithDefaults();

        $data = [
            'containerWidth' => $s['container_width'].'px',
            'contentFontFamily' => $s['content_font_family'],
            'contentFontSize' => $s['content_font_size'].'px',
            'contentLineHeight' => $s['content_line_height'],
            'contentTextColor' => $s['content_text_color'],
            'contentBgColor' => $s['content_bg_color'],
            'headingFontFamily' => $s['heading_font_family'],
            'headingColor' => $s['heading_color'],
            'headingFontWeight' => $s['heading_font_weight'],
            'titleAlignment' => $s['title_alignment'],
            'titlePaddingY' => $s['title_padding_y'].'px',
            'titleBgColor' => $s['title_bg_color'],
            'borderRadius' => $s['border_radius'].'px',
            'shadowEnabled' => $s['shadow_enabled'],
            'shadowColor' => $s['shadow_color'],
            'mobileBreakpoint' => $s['mobile_breakpoint'].'px',
        ];

        return $this->safeSubstitute($this->css, $data);
    }

    /**
     * Render the JS template.
     */
    public function renderJs(): string
    {
        return $this->js ?? '';
    }

    // ── Default Templates ──

    public static function defaultHtmlTemplate(): string
    {
        return <<<'HTML'
<div class="ssm-page-template">
    @if ($showTitle)
    <div class="ssm-page-template__hero">
        <div class="ssm-page-template__container">
            <h1 class="ssm-page-template__title">{{ $pageTitle }}</h1>
        </div>
    </div>
    @endif

    <div class="ssm-page-template__body">
        <div class="ssm-page-template__container">
            <article class="ssm-page-template__content {!! $enableAnimation ? 'ssm-animate' : '' !!}" data-animation="{{ $animationStyle }}">
                {!! $pageContent !!}
            </article>
        </div>
    </div>
</div>
HTML;
    }

    public static function defaultCssTemplate(): string
    {
        return <<<'CSS'
/* ── Page Template Styles ── */
:root {
    --pt-container: {{ $containerWidth }};
    --pt-content-font: {{ $contentFontFamily }};
    --pt-content-fs: {{ $contentFontSize }};
    --pt-content-lh: {{ $contentLineHeight }};
    --pt-content-color: {{ $contentTextColor }};
    --pt-content-bg: {{ $contentBgColor }};
    --pt-heading-font: {{ $headingFontFamily }};
    --pt-heading-color: {{ $headingColor }};
    --pt-heading-fw: {{ $headingFontWeight }};
    --pt-title-align: {{ $titleAlignment }};
    --pt-title-py: {{ $titlePaddingY }};
    --pt-title-bg: {{ $titleBgColor }};
    --pt-radius: {{ $borderRadius }};
    --pt-shadow: {{ $shadowColor }};
    --pt-bp-mobile: {{ $mobileBreakpoint }};
    --ssm-blue: #083F7F;
    --ssm-navy: #052D64;
    --ssm-gold: #FBAB03;
}

.ssm-page-template {
    font-family: var(--pt-content-font);
    color: var(--pt-content-color);
    background: var(--pt-content-bg);
    min-height: 60vh;
}

.ssm-page-template__hero {
    background: var(--pt-title-bg);
    padding: var(--pt-title-py) 24px;
    text-align: var(--pt-title-align);
    border-bottom: 1px solid rgba(8, 63, 127, 0.06);
}

.ssm-page-template__container {
    max-width: var(--pt-container);
    margin: 0 auto;
    padding: 0 24px;
}

.ssm-page-template__title {
    font-family: var(--pt-heading-font);
    font-weight: var(--pt-heading-fw);
    font-size: 2.5rem;
    color: var(--pt-heading-color);
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.03em;
}

.ssm-page-template__body {
    padding: 48px 24px;
}

.ssm-page-template__content {
    font-size: var(--pt-content-fs);
    line-height: var(--pt-content-lh);
    color: var(--pt-content-color);
    max-width: 800px;
    margin: 0 auto;
}

.ssm-page-template__content h2 {
    font-family: var(--pt-heading-font);
    font-weight: var(--pt-heading-fw);
    font-size: 1.75rem;
    color: var(--pt-heading-color);
    margin: 40px 0 16px;
    letter-spacing: -0.02em;
}

.ssm-page-template__content h3 {
    font-family: var(--pt-heading-font);
    font-weight: 600;
    font-size: 1.35rem;
    color: var(--pt-heading-color);
    margin: 32px 0 12px;
}

.ssm-page-template__content p {
    margin: 0 0 20px;
}

.ssm-page-template__content a {
    color: var(--ssm-blue);
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.2s ease;
}

.ssm-page-template__content a:hover {
    color: var(--ssm-navy);
}

.ssm-page-template__content blockquote {
    border-left: 4px solid var(--ssm-gold);
    padding: 16px 24px;
    margin: 24px 0;
    background: rgba(251, 171, 3, 0.04);
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: #4b5563;
}

.ssm-page-template__content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 24px 0;
}

.ssm-page-template__content ul,
.ssm-page-template__content ol {
    margin: 0 0 20px;
    padding-left: 24px;
}

.ssm-page-template__content li {
    margin-bottom: 8px;
}

/* ── Animations ── */
.ssm-animate {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.ssm-animate.is-visible {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: {{ $mobileBreakpoint }}) {
    .ssm-page-template__title {
        font-size: 1.75rem;
    }

    .ssm-page-template__hero {
        padding: 40px 16px;
    }

    .ssm-page-template__body {
        padding: 32px 16px;
    }

    .ssm-page-template__container {
        padding: 0 16px;
    }

    .ssm-page-template__content h2 {
        font-size: 1.4rem;
    }
}
CSS;
    }

    public static function defaultJsTemplate(): string
    {
        return <<<'JS'
(function() {
    'use strict';

    // Scroll-triggered visibility animation
    var animated = document.querySelector('.ssm-animate');
    if (animated && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(animated);
    } else if (animated) {
        animated.classList.add('is-visible');
    }
})();
JS;
    }

    /**
     * Restricted template substitution: only `{{ $identifier }}` (HTML-escaped) and
     * `{!! $identifier !!}` (raw) referencing a bare variable name are recognized.
     */
    public static function safeSubstitute(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{!!\s*\$(\\w+)\s*!!\}|\{\{\s*\$(\\w+)\s*\}\}/',
            function (array $m) use ($data) {
                if ($m[1] !== '') {
                    return (string) ($data[$m[1]] ?? '');
                }

                return e((string) ($data[$m[2]] ?? ''));
            },
            $template
        );
    }
}

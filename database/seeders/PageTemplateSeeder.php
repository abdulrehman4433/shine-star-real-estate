<?php

namespace Database\Seeders;

use App\Models\PageTemplate;
use Illuminate\Database\Seeder;

class PageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
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
            'title_alignment' => 'center',
            'title_bg_color' => '#f8fafc',
            'title_padding_y' => 80,
            'border_radius' => 12,
            'shadow_enabled' => false,
            'shadow_color' => 'rgba(0,0,0,0.08)',
            'enable_animation' => true,
            'animation_style' => 'fade-up',
            'mobile_breakpoint' => 768,
        ];

        // ── Premium Page Layout ──
        PageTemplate::firstOrCreate(
            ['name' => 'Premium Page Layout'],
            [
                'status' => 'published',
                'is_active' => true,
                'html' => self::premiumHtml(),
                'css' => self::premiumCss(),
                'js' => self::premiumJs(),
                'settings' => $settings,
            ]
        );

        // ── Home Page ──
        $homeSettings = array_merge($settings, [
            'container_width' => 1280,
            'show_title' => false,
            'content_bg_color' => '#ffffff',
        ]);

        PageTemplate::firstOrCreate(
            ['name' => 'Home Page'],
            [
                'status' => 'published',
                'is_active' => false,
                'html' => self::homeHtml(),
                'css' => self::homeCss(),
                'js' => self::homeJs(),
                'settings' => $homeSettings,
            ]
        );

        // ── About Us ──
        $aboutSettings = array_merge($settings, [
            'container_width' => 1152,
            'show_title' => true,
            'title_alignment' => 'left',
            'title_bg_color' => '#052D64',
            'content_bg_color' => '#ffffff',
            'heading_color' => '#083F7F',
            'enable_animation' => true,
        ]);

        PageTemplate::firstOrCreate(
            ['name' => 'About Us'],
            [
                'status' => 'published',
                'is_active' => false,
                'html' => self::aboutHtml(),
                'css' => self::aboutCss(),
                'js' => self::aboutJs(),
                'settings' => $aboutSettings,
            ]
        );
    }

    // ════════════════════════════════════════
    //  PREMIUM PAGE LAYOUT
    // ════════════════════════════════════════

    public static function premiumHtml(): string
    {
        return <<<'HTML'
<div class="pt-page">
    @if ($showTitle)
    <section class="pt-hero">
        <div class="pt-container">
            <h1 class="pt-hero__title">{{ $pageTitle }}</h1>
            <nav class="pt-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="/">Home</a></li>
                    <li aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
    </section>
    @endif

    <section class="pt-body">
        <div class="pt-container">
            <article class="pt-content {!! $enableAnimation ? 'pt-animate' : '' !!}" data-animation="{{ $animationStyle }}">
                {!! $pageContent !!}
            </article>
        </div>
    </section>

    <div class="pt-wave" aria-hidden="true">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
            <path d="M0,40 C320,100 640,0 960,40 C1280,80 1360,20 1440,40 L1440,80 L0,80 Z" fill="rgba(8,63,127,0.03)"/>
        </svg>
    </div>
</div>
HTML;
    }

    public static function premiumCss(): string
    {
        return <<<'CSS'
:root {
    --pt-container: {{ $containerWidth }}px;
    --pt-body-font: {{ $contentFontFamily }};
    --pt-body-fs: {{ $contentFontSize }}px;
    --pt-body-lh: {{ $contentLineHeight }};
    --pt-body-color: {{ $contentTextColor }};
    --pt-body-bg: {{ $contentBgColor }};
    --pt-heading-font: {{ $headingFontFamily }};
    --pt-heading-color: {{ $headingColor }};
    --pt-heading-fw: {{ $headingFontWeight }};
    --pt-title-align: {{ $titleAlignment }};
    --pt-title-py: {{ $titlePaddingY }}px;
    --pt-title-bg: {{ $titleBgColor }};
    --pt-radius: {{ $borderRadius }}px;
    --pt-shadow: {{ $shadowColor }};
    --pt-bp-mobile: {{ $mobileBreakpoint }}px;
    --ssm-blue: #083F7F;
    --ssm-navy: #052D64;
    --ssm-gold: #FBAB03;
}

.pt-page {
    font-family: var(--pt-body-font);
    color: var(--pt-body-color);
    background: var(--pt-body-bg);
    min-height: 60vh;
    position: relative;
}

.pt-container {
    max-width: var(--pt-container);
    margin: 0 auto;
    padding: 0 24px;
}

.pt-hero {
    background: linear-gradient(135deg, var(--pt-title-bg) 0%, #ffffff 100%);
    padding: var(--pt-title-py) 24px;
    text-align: var(--pt-title-align);
    border-bottom: 1px solid rgba(8, 63, 127, 0.06);
    position: relative;
    overflow: hidden;
}

.pt-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(8,63,127,0.04) 0%, transparent 70%);
    pointer-events: none;
}

.pt-hero__title {
    font-family: var(--pt-heading-font);
    font-weight: var(--pt-heading-fw);
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    color: var(--pt-heading-color);
    margin: 0 0 12px;
    line-height: 1.15;
    letter-spacing: -0.03em;
    position: relative;
}

.pt-breadcrumb ol {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: var(--pt-title-align);
    gap: 8px;
    font-size: 13px;
    color: #6b7280;
}

.pt-breadcrumb li:not(:last-child)::after { content: '/'; margin-left: 8px; color: #d1d5db; }
.pt-breadcrumb a { color: var(--ssm-blue); text-decoration: none; transition: color 0.2s ease; }
.pt-breadcrumb a:hover { color: var(--ssm-navy); text-decoration: underline; text-underline-offset: 2px; }
.pt-breadcrumb [aria-current="page"] { color: #6b7280; font-weight: 500; }

.pt-body { padding: 56px 24px 80px; }

.pt-content { font-size: var(--pt-body-fs); line-height: var(--pt-body-lh); color: var(--pt-body-color); }
.pt-content h2 { font-family: var(--pt-heading-font); font-weight: var(--pt-heading-fw); font-size: 1.75rem; color: var(--pt-heading-color); margin: 48px 0 16px; letter-spacing: -0.02em; line-height: 1.25; }
.pt-content h2:first-child { margin-top: 0; }
.pt-content h3 { font-family: var(--pt-heading-font); font-weight: 600; font-size: 1.35rem; color: var(--pt-heading-color); margin: 36px 0 12px; line-height: 1.3; }
.pt-content p { margin: 0 0 24px; }
.pt-content a { color: var(--ssm-blue); text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; transition: color 0.2s ease, text-decoration-color 0.2s ease; }
.pt-content a:hover { color: var(--ssm-navy); text-decoration-color: var(--ssm-gold); }
.pt-content blockquote { border-left: 4px solid var(--ssm-gold); padding: 20px 24px; margin: 32px 0; background: linear-gradient(135deg, rgba(251, 171, 3, 0.04), rgba(251, 171, 3, 0.01)); border-radius: 0 12px 12px 0; font-style: italic; color: #4b5563; font-size: 1.05em; }
.pt-content img { max-width: 100%; height: auto; border-radius: var(--pt-radius); margin: 32px 0; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: box-shadow 0.3s ease, transform 0.3s ease; }
.pt-content img:hover { box-shadow: 0 8px 40px rgba(0,0,0,0.10); transform: translateY(-2px); }
.pt-content ul, .pt-content ol { margin: 0 0 24px; padding-left: 24px; }
.pt-content li { margin-bottom: 10px; }
.pt-content ul li::marker { color: var(--ssm-blue); }
.pt-content hr { border: none; height: 1px; background: linear-gradient(90deg, transparent, rgba(8,63,127,0.15), transparent); margin: 48px 0; }
.pt-content table { width: 100%; border-collapse: collapse; margin: 32px 0; font-size: 0.95em; }
.pt-content th, .pt-content td { padding: 12px 16px; border: 1px solid #e5e7eb; text-align: left; }
.pt-content th { background: rgba(8,63,127,0.04); font-weight: 600; color: var(--pt-heading-color); }
.pt-content code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; color: #1e293b; }
.pt-content pre { background: #1e293b; color: #e2e8f0; padding: 20px 24px; border-radius: var(--pt-radius); overflow-x: auto; margin: 32px 0; font-size: 14px; line-height: 1.6; }
.pt-content pre code { background: none; padding: 0; color: inherit; font-size: inherit; }

.pt-wave { width: 100%; overflow: hidden; line-height: 0; margin-top: -2px; }
.pt-wave svg { width: 100%; height: 80px; }

.pt-animate { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
.pt-animate.is-visible { opacity: 1; transform: translateY(0); }

@media (max-width: {{ $mobileBreakpoint }}px) {
    .pt-hero { padding: 48px 16px; }
    .pt-hero__title { font-size: 1.65rem; }
    .pt-body { padding: 36px 16px 60px; }
    .pt-container { padding: 0 16px; }
    .pt-content h2 { font-size: 1.35rem; margin-top: 36px; }
    .pt-content h3 { font-size: 1.15rem; }
    .pt-content blockquote { padding: 16px 20px; }
    .pt-content table { font-size: 0.85em; }
    .pt-content th, .pt-content td { padding: 8px 10px; }
    .pt-breadcrumb ol { font-size: 12px; flex-wrap: wrap; }
}
CSS;
    }

    public static function premiumJs(): string
    {
        return <<<'JS'
(function(){'use strict';
var a=document.querySelector('.pt-animate');
if(a&&'IntersectionObserver'in window){var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){e.target.classList.add('is-visible');o.unobserve(e.target)}})},{threshold:0.08});o.observe(a)}else if(a){a.classList.add('is-visible')}
document.querySelectorAll('.pt-content a[href^="#"]').forEach(function(l){l.addEventListener('click',function(e){var t=document.querySelector(this.getAttribute('href'));if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}})});
document.querySelectorAll('.pt-content table').forEach(function(t){var w=document.createElement('div');w.style.cssText='overflow-x:auto;-webkit-overflow-scrolling:touch;margin:32px 0;';t.style.margin='0';t.parentNode.insertBefore(w,t);w.appendChild(t)});
if('loading'in HTMLImageElement.prototype){document.querySelectorAll('.pt-content img:not([loading])').forEach(function(i){i.loading='lazy'})}
})();
JS;
    }

    // ════════════════════════════════════════
    //  HOME PAGE
    // ════════════════════════════════════════

    public static function homeHtml(): string
    {
        return <<<'HTML'
<div class="hp-page">
    {{-- Hero Banner --}}
    <section class="hp-hero">
        <div class="hp-hero__bg" aria-hidden="true"></div>
        <div class="hp-container">
            <div class="hp-hero__content {!! $enableAnimation ? 'hp-animate hp-animate--fade' : '' !!}">
                <span class="hp-hero__badge">Welcome</span>
                <h1 class="hp-hero__title">{{ $pageTitle }}</h1>
                <p class="hp-hero__sub">Discover premium real estate opportunities tailored to your vision.</p>
                <div class="hp-hero__actions">
                    <a href="/properties" class="hp-btn hp-btn--primary">Explore Properties</a>
                    <a href="/contact" class="hp-btn hp-btn--outline">Get in Touch</a>
                </div>
            </div>
        </div>
        <div class="hp-hero__wave" aria-hidden="true">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path d="M0,60 C360,120 720,0 1080,60 C1260,90 1350,40 1440,60 L1440,120 L0,120 Z" fill="white"/>
            </svg>
        </div>
    </section>

    {{-- Features Strip --}}
    <section class="hp-features {!! $enableAnimation ? 'hp-animate hp-animate--up' : '' !!}">
        <div class="hp-container">
            <div class="hp-features__grid">
                <div class="hp-feature-card">
                    <div class="hp-feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>
                    <h3>Prime Locations</h3>
                    <p>Hand-picked properties in the most sought-after neighborhoods.</p>
                </div>
                <div class="hp-feature-card">
                    <div class="hp-feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <h3>Expert Guidance</h3>
                    <p>Experienced agents dedicated to finding your perfect match.</p>
                </div>
                <div class="hp-feature-card">
                    <div class="hp-feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                    <h3>Trusted Service</h3>
                    <p>Transparent, reliable, and always putting you first.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content Body --}}
    <section class="hp-body">
        <div class="hp-container hp-container--narrow">
            <article class="hp-content">
                {!! $pageContent !!}
            </article>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="hp-cta {!! $enableAnimation ? 'hp-animate hp-animate--up' : '' !!}">
        <div class="hp-container">
            <div class="hp-cta__card">
                <h2 class="hp-cta__title">Ready to Take the Next Step?</h2>
                <p class="hp-cta__text">Schedule a consultation with one of our experts today.</p>
                <a href="/contact" class="hp-btn hp-btn--gold">Contact Us</a>
            </div>
        </div>
    </section>
</div>
HTML;
    }

    public static function homeCss(): string
    {
        return <<<'CSS'
:root {
    --hp-container: {{ $containerWidth }}px;
    --hp-body-font: {{ $contentFontFamily }};
    --hp-color: {{ $contentTextColor }};
    --hp-heading-font: {{ $headingFontFamily }};
    --hp-heading-color: {{ $headingColor }};
    --hp-heading-fw: {{ $headingFontWeight }};
    --hp-radius: {{ $borderRadius }}px;
    --hp-bp-mobile: {{ $mobileBreakpoint }}px;
    --ssm-blue: #083F7F;
    --ssm-navy: #052D64;
    --ssm-gold: #FBAB03;
}

.hp-page {
    font-family: var(--hp-body-font);
    color: var(--hp-color);
    background: #ffffff;
    overflow-x: hidden;
}

.hp-container {
    max-width: var(--hp-container);
    margin: 0 auto;
    padding: 0 24px;
}

.hp-container--narrow { max-width: 896px; }

/* ── Hero Section ── */
.hp-hero {
    position: relative;
    padding: 100px 24px 80px;
    background: linear-gradient(160deg, #f0f5ff 0%, #e8f0fe 40%, #dce6f5 100%);
    overflow: hidden;
    min-height: 480px;
    display: flex;
    align-items: center;
}

.hp-hero__bg {
    position: absolute;
    top: -60%;
    right: -20%;
    width: 700px;
    height: 700px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(8,63,127,0.06) 0%, transparent 70%);
    pointer-events: none;
}

.hp-hero__bg::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(251,171,3,0.04) 0%, transparent 70%);
    pointer-events: none;
}

.hp-hero__content {
    position: relative;
    z-index: 1;
    max-width: 720px;
}

.hp-hero__badge {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ssm-blue);
    background: rgba(8,63,127,0.08);
    padding: 6px 14px;
    border-radius: 999px;
    margin-bottom: 20px;
}

.hp-hero__title {
    font-family: var(--hp-heading-font);
    font-weight: var(--hp-heading-fw);
    font-size: clamp(2rem, 5vw, 3.5rem);
    color: var(--ssm-navy);
    line-height: 1.1;
    letter-spacing: -0.04em;
    margin: 0 0 16px;
}

.hp-hero__sub {
    font-size: 1.15rem;
    line-height: 1.6;
    color: #4b5563;
    margin: 0 0 32px;
    max-width: 540px;
}

.hp-hero__actions { display: flex; gap: 12px; flex-wrap: wrap; }

.hp-btn {
    display: inline-flex;
    align-items: center;
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 600;
    border-radius: var(--hp-radius);
    text-decoration: none;
    transition: all 0.25s ease;
    white-space: nowrap;
    border: 2px solid transparent;
}

.hp-btn--primary {
    background: var(--ssm-blue);
    color: #fff;
    border-color: var(--ssm-blue);
    box-shadow: 0 4px 14px rgba(8,63,127,0.25);
}
.hp-btn--primary:hover { background: var(--ssm-navy); border-color: var(--ssm-navy); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(8,63,127,0.30); }

.hp-btn--outline {
    background: transparent;
    color: var(--ssm-blue);
    border-color: var(--ssm-blue);
}
.hp-btn--outline:hover { background: var(--ssm-blue); color: #fff; transform: translateY(-2px); }

.hp-btn--gold {
    background: var(--ssm-gold);
    color: var(--ssm-navy);
    border-color: var(--ssm-gold);
    box-shadow: 0 4px 14px rgba(251,171,3,0.30);
}
.hp-btn--gold:hover { background: #e09c00; border-color: #e09c00; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(251,171,3,0.35); }

.hp-hero__wave {
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    line-height: 0;
    overflow: hidden;
}
.hp-hero__wave svg { width: 100%; height: 100px; }

@media (max-width: 768px) {
    .hp-hero__wave svg { height: 50px; }
}

/* ── Features Strip ── */
.hp-features {
    padding: 80px 0;
    background: #ffffff;
}

.hp-features__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .hp-features__grid { grid-template-columns: 1fr; }
}

.hp-feature-card {
    text-align: center;
    padding: 40px 24px;
    border-radius: var(--hp-radius);
    background: #f9fafb;
    transition: all 0.3s ease;
}

.hp-feature-card:hover {
    background: #ffffff;
    box-shadow: 0 8px 32px rgba(8,63,127,0.08);
    transform: translateY(-4px);
}

.hp-feature-card__icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 20px;
    color: var(--ssm-blue);
    background: rgba(8,63,127,0.06);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease, color 0.3s ease;
}

.hp-feature-card:hover .hp-feature-card__icon {
    background: var(--ssm-blue);
    color: #fff;
}

.hp-feature-card__icon svg { width: 28px; height: 28px; }

.hp-feature-card h3 {
    font-family: var(--hp-heading-font);
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--ssm-navy);
    margin: 0 0 8px;
}

.hp-feature-card p {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}

/* ── Body Section ── */
.hp-body {
    padding: 64px 24px 80px;
}

.hp-content {
    font-size: {{ $contentFontSize }}px;
    line-height: {{ $contentLineHeight }};
}

.hp-content h2 {
    font-family: var(--hp-heading-font);
    font-weight: var(--hp-heading-fw);
    font-size: 1.75rem;
    color: var(--hp-heading-color);
    margin: 48px 0 16px;
    letter-spacing: -0.02em;
    line-height: 1.25;
}
.hp-content h2:first-child { margin-top: 0; }
.hp-content h3 {
    font-family: var(--hp-heading-font);
    font-weight: 600;
    font-size: 1.35rem;
    color: var(--hp-heading-color);
    margin: 36px 0 12px;
    line-height: 1.3;
}
.hp-content p { margin: 0 0 24px; }
.hp-content a { color: var(--ssm-blue); text-decoration: underline; text-underline-offset: 3px; }
.hp-content a:hover { color: var(--ssm-navy); text-decoration-color: var(--ssm-gold); }
.hp-content blockquote { border-left: 4px solid var(--ssm-gold); padding: 20px 24px; margin: 32px 0; background: linear-gradient(135deg, rgba(251,171,3,0.04), rgba(251,171,3,0.01)); border-radius: 0 12px 12px 0; font-style: italic; color: #4b5563; }
.hp-content img { max-width: 100%; height: auto; border-radius: var(--hp-radius); margin: 32px 0; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
.hp-content ul, .hp-content ol { margin: 0 0 24px; padding-left: 24px; }
.hp-content li { margin-bottom: 10px; }
.hp-content hr { border: none; height: 1px; background: linear-gradient(90deg, transparent, rgba(8,63,127,0.15), transparent); margin: 48px 0; }

/* ── CTA Section ── */
.hp-cta {
    padding: 0 24px 80px;
}

.hp-cta__card {
    text-align: center;
    padding: 64px 40px;
    border-radius: 16px;
    background: linear-gradient(160deg, #f0f5ff 0%, #e8f0fe 100%);
    position: relative;
    overflow: hidden;
}

.hp-cta__card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(8,63,127,0.04) 0%, transparent 70%);
    pointer-events: none;
}

.hp-cta__title {
    font-family: var(--hp-heading-font);
    font-weight: var(--hp-heading-fw);
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    color: var(--ssm-navy);
    margin: 0 0 12px;
    letter-spacing: -0.03em;
    position: relative;
}

.hp-cta__text {
    font-size: 1.05rem;
    color: #4b5563;
    margin: 0 0 28px;
    position: relative;
}

/* ── Animations ── */
.hp-animate { opacity: 0; transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
.hp-animate--fade { transform: translateY(20px); }
.hp-animate--up { transform: translateY(40px); }
.hp-animate.is-visible { opacity: 1; transform: translateY(0); }

/* ── Responsive ── */
@media (max-width: {{ $mobileBreakpoint }}px) {
    .hp-hero { padding: 72px 16px 60px; min-height: auto; }
    .hp-hero__title { font-size: 1.75rem; }
    .hp-hero__sub { font-size: 1rem; }
    .hp-hero__actions { flex-direction: column; }
    .hp-btn { justify-content: center; }
    .hp-features { padding: 48px 0; }
    .hp-feature-card { padding: 28px 20px; }
    .hp-body { padding: 40px 16px 60px; }
    .hp-content h2 { font-size: 1.35rem; }
    .hp-cta { padding: 0 16px 60px; }
    .hp-cta__card { padding: 40px 24px; }
}
CSS;
    }

    public static function homeJs(): string
    {
        return <<<'JS'
(function(){'use strict';
var els=document.querySelectorAll('.hp-animate');
if('IntersectionObserver'in window&&els.length){var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){e.target.classList.add('is-visible');o.unobserve(e.target)}})},{threshold:0.08});els.forEach(function(e){o.observe(e)})}else{els.forEach(function(e){e.classList.add('is-visible')})}
document.querySelectorAll('.hp-content a[href^="#"]').forEach(function(l){l.addEventListener('click',function(e){var t=document.querySelector(this.getAttribute('href'));if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}})});
document.querySelectorAll('.hp-content table').forEach(function(t){var w=document.createElement('div');w.style.cssText='overflow-x:auto;-webkit-overflow-scrolling:touch;margin:32px 0;';t.style.margin='0';t.parentNode.insertBefore(w,t);w.appendChild(t)});
if('loading'in HTMLImageElement.prototype){document.querySelectorAll('.hp-content img:not([loading])').forEach(function(i){i.loading='lazy'})}
})();
JS;
    }

    // ════════════════════════════════════════
    //  ABOUT US
    // ════════════════════════════════════════

    public static function aboutHtml(): string
    {
        return <<<'HTML'
<div class="ab-page">
    {{-- Hero / Title Section (Dark Theme) --}}
    @if ($showTitle)
    <section class="ab-hero">
        <div class="ab-container">
            <nav class="ab-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="/">Home</a></li>
                    <li aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
            <h1 class="ab-hero__title">{{ $pageTitle }}</h1>
            <p class="ab-hero__sub">Learn about our story, values, and the team behind Shine Star Marketing.</p>
        </div>
        <div class="ab-hero__pattern" aria-hidden="true"></div>
    </section>
    @endif

    {{-- Mission Section --}}
    <section class="ab-mission {!! $enableAnimation ? 'ab-animate' : '' !!}">
        <div class="ab-container">
            <div class="ab-mission__grid">
                <div class="ab-mission__card">
                    <div class="ab-mission__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To redefine the real estate experience with integrity, innovation, and unwavering dedication to our clients.</p>
                </div>
                <div class="ab-mission__card">
                    <div class="ab-mission__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <h3>Our Values</h3>
                    <p>Trust, transparency, and long-term relationships are at the heart of everything we do.</p>
                </div>
                <div class="ab-mission__card">
                    <div class="ab-mission__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3>Our Team</h3>
                    <p>A diverse group of passionate professionals united by a shared commitment to excellence.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Story / Main Content --}}
    <section class="ab-story">
        <div class="ab-container">
            <div class="ab-story__inner">
                <div class="ab-story__content">
                    {!! $pageContent !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Strip --}}
    <section class="ab-stats {!! $enableAnimation ? 'ab-animate' : '' !!}">
        <div class="ab-container">
            <div class="ab-stats__grid">
                <div class="ab-stat">
                    <span class="ab-stat__number">150+</span>
                    <span class="ab-stat__label">Properties Sold</span>
                </div>
                <div class="ab-stat">
                    <span class="ab-stat__number">98%</span>
                    <span class="ab-stat__label">Client Satisfaction</span>
                </div>
                <div class="ab-stat">
                    <span class="ab-stat__number">12+</span>
                    <span class="ab-stat__label">Years Experience</span>
                </div>
                <div class="ab-stat">
                    <span class="ab-stat__number">5★</span>
                    <span class="ab-stat__label">Average Rating</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Contact CTA --}}
    <section class="ab-cta">
        <div class="ab-container">
            <div class="ab-cta__inner">
                <h2 class="ab-cta__title">Want to Know More?</h2>
                <p class="ab-cta__text">We'd love to hear from you. Reach out and start a conversation.</p>
                <a href="/contact" class="ab-btn">Contact Us</a>
            </div>
        </div>
    </section>
</div>
HTML;
    }

    public static function aboutCss(): string
    {
        return <<<'CSS'
:root {
    --ab-container: {{ $containerWidth }}px;
    --ab-body-font: {{ $contentFontFamily }};
    --ab-body-fs: {{ $contentFontSize }}px;
    --ab-body-lh: {{ $contentLineHeight }};
    --ab-color: {{ $contentTextColor }};
    --ab-bg: {{ $contentBgColor }};
    --ab-heading-font: {{ $headingFontFamily }};
    --ab-heading-color: {{ $headingColor }};
    --ab-heading-fw: {{ $headingFontWeight }};
    --ab-radius: {{ $borderRadius }}px;
    --ab-bp-mobile: {{ $mobileBreakpoint }}px;
    --ssm-blue: #083F7F;
    --ssm-navy: #052D64;
    --ssm-gold: #FBAB03;
}

.ab-page {
    font-family: var(--ab-body-font);
    color: var(--ab-color);
    background: var(--ab-bg);
    overflow-x: hidden;
}

.ab-container {
    max-width: var(--ab-container);
    margin: 0 auto;
    padding: 0 24px;
}

/* ── Hero Section (Dark Navy) ── */
.ab-hero {
    position: relative;
    background: linear-gradient(135deg, var(--ssm-navy) 0%, #041f43 100%);
    padding: 60px 24px 80px;
    overflow: hidden;
}

.ab-hero__title {
    font-family: var(--ab-heading-font);
    font-weight: var(--ab-heading-fw);
    font-size: clamp(2rem, 4vw, 3rem);
    color: #ffffff;
    margin: 16px 0 12px;
    letter-spacing: -0.03em;
    line-height: 1.1;
    position: relative;
    z-index: 1;
}

.ab-hero__sub {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.7);
    margin: 0;
    max-width: 560px;
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

.ab-breadcrumb ol {
    list-style: none;
    margin: 0 0 8px;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    position: relative;
    z-index: 1;
}

.ab-breadcrumb li:not(:last-child)::after { content: '/'; margin-left: 8px; color: rgba(255,255,255,0.3); }
.ab-breadcrumb a { color: var(--ssm-gold); text-decoration: none; transition: color 0.2s ease; }
.ab-breadcrumb a:hover { color: #ffc84a; text-decoration: underline; text-underline-offset: 2px; }
.ab-breadcrumb [aria-current="page"] { color: rgba(255,255,255,0.5); font-weight: 500; }

.ab-hero__pattern {
    position: absolute;
    top: 0; right: 0;
    width: 50%;
    height: 100%;
    background: radial-gradient(circle at 70% 30%, rgba(251,171,3,0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.03) 0%, transparent 40%);
    pointer-events: none;
}

/* ── Mission Section ── */
.ab-mission {
    padding: 80px 0;
    background: #ffffff;
}

.ab-mission__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .ab-mission__grid { grid-template-columns: 1fr; }
}

.ab-mission__card {
    text-align: center;
    padding: 44px 28px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.ab-mission__card:hover {
    border-color: var(--ssm-blue);
    box-shadow: 0 8px 32px rgba(8,63,127,0.08);
    transform: translateY(-4px);
}

.ab-mission__icon {
    width: 52px;
    height: 52px;
    margin: 0 auto 20px;
    color: var(--ssm-blue);
    background: rgba(8,63,127,0.06);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease, color 0.3s ease;
}

.ab-mission__card:hover .ab-mission__icon {
    background: var(--ssm-blue);
    color: #fff;
}

.ab-mission__icon svg { width: 26px; height: 26px; }

.ab-mission__card h3 {
    font-family: var(--ab-heading-font);
    font-weight: 700;
    font-size: 1.15rem;
    color: var(--ssm-navy);
    margin: 0 0 8px;
}

.ab-mission__card p {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}

/* ── Story Section ── */
.ab-story {
    padding: 64px 0 80px;
    background: #f9fafb;
}

.ab-story__inner {
    max-width: 896px;
    margin: 0 auto;
}

.ab-story__content {
    font-size: var(--ab-body-fs);
    line-height: var(--ab-body-lh);
}

.ab-story__content h2 {
    font-family: var(--ab-heading-font);
    font-weight: var(--ab-heading-fw);
    font-size: 1.75rem;
    color: var(--ab-heading-color);
    margin: 48px 0 16px;
    letter-spacing: -0.02em;
    line-height: 1.25;
}
.ab-story__content h2:first-child { margin-top: 0; }
.ab-story__content h3 {
    font-family: var(--ab-heading-font);
    font-weight: 600;
    font-size: 1.35rem;
    color: var(--ab-heading-color);
    margin: 36px 0 12px;
    line-height: 1.3;
}
.ab-story__content p { margin: 0 0 24px; }
.ab-story__content a { color: var(--ssm-blue); text-decoration: underline; text-underline-offset: 3px; }
.ab-story__content a:hover { color: var(--ssm-navy); text-decoration-color: var(--ssm-gold); }
.ab-story__content blockquote { border-left: 4px solid var(--ssm-gold); padding: 20px 24px; margin: 32px 0; background: linear-gradient(135deg, rgba(251,171,3,0.04), rgba(251,171,3,0.01)); border-radius: 0 12px 12px 0; font-style: italic; color: #4b5563; }
.ab-story__content img { max-width: 100%; height: auto; border-radius: var(--ab-radius); margin: 32px 0; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
.ab-story__content ul, .ab-story__content ol { margin: 0 0 24px; padding-left: 24px; }
.ab-story__content li { margin-bottom: 10px; }

/* ── Stats Strip ── */
.ab-stats {
    padding: 64px 0;
    background: var(--ssm-navy);
}

.ab-stats__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .ab-stats__grid { grid-template-columns: repeat(2, 1fr); gap: 32px; }
}

.ab-stat {
    text-align: center;
}

.ab-stat__number {
    display: block;
    font-family: var(--ab-heading-font);
    font-weight: 800;
    font-size: 2.5rem;
    color: var(--ssm-gold);
    line-height: 1;
    margin-bottom: 8px;
}

.ab-stat__label {
    display: block;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.6);
    font-weight: 500;
}

/* ── CTA Section ── */
.ab-cta {
    padding: 80px 24px;
    background: #ffffff;
}

.ab-cta__inner {
    text-align: center;
    padding: 56px 40px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f0f5ff 0%, #e8f0fe 100%);
}

.ab-cta__title {
    font-family: var(--ab-heading-font);
    font-weight: var(--ab-heading-fw);
    font-size: clamp(1.5rem, 3vw, 2rem);
    color: var(--ssm-navy);
    margin: 0 0 12px;
    letter-spacing: -0.03em;
}

.ab-cta__text {
    font-size: 1.05rem;
    color: #4b5563;
    margin: 0 0 28px;
}

.ab-btn {
    display: inline-flex;
    align-items: center;
    padding: 14px 32px;
    font-size: 15px;
    font-weight: 600;
    border-radius: var(--ab-radius);
    text-decoration: none;
    transition: all 0.25s ease;
    background: var(--ssm-blue);
    color: #fff;
    box-shadow: 0 4px 14px rgba(8,63,127,0.25);
}

.ab-btn:hover {
    background: var(--ssm-navy);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(8,63,127,0.30);
}

/* ── Animations ── */
.ab-animate { opacity: 0; transform: translateY(40px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
.ab-animate.is-visible { opacity: 1; transform: translateY(0); }

/* ── Responsive ── */
@media (max-width: {{ $mobileBreakpoint }}px) {
    .ab-hero { padding: 40px 16px 60px; }
    .ab-hero__title { font-size: 1.75rem; }
    .ab-hero__sub { font-size: 1rem; }
    .ab-mission { padding: 48px 0; }
    .ab-mission__card { padding: 32px 20px; }
    .ab-story { padding: 40px 0 60px; }
    .ab-story__content h2 { font-size: 1.35rem; }
    .ab-stats { padding: 48px 0; }
    .ab-stat__number { font-size: 2rem; }
    .ab-cta { padding: 60px 16px; }
    .ab-cta__inner { padding: 40px 24px; }
}
CSS;
    }

    public static function aboutJs(): string
    {
        return <<<'JS'
(function(){'use strict';
var els=document.querySelectorAll('.ab-animate');
if('IntersectionObserver'in window&&els.length){var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){e.target.classList.add('is-visible');o.unobserve(e.target)}})},{threshold:0.08});els.forEach(function(e){o.observe(e)})}else{els.forEach(function(e){e.classList.add('is-visible')})}
document.querySelectorAll('.ab-story__content a[href^="#"]').forEach(function(l){l.addEventListener('click',function(e){var t=document.querySelector(this.getAttribute('href'));if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}})});
document.querySelectorAll('.ab-story__content table').forEach(function(t){var w=document.createElement('div');w.style.cssText='overflow-x:auto;-webkit-overflow-scrolling:touch;margin:32px 0;';t.style.margin='0';t.parentNode.insertBefore(w,t);w.appendChild(t)});
if('loading'in HTMLImageElement.prototype){document.querySelectorAll('.ab-story__content img:not([loading])').forEach(function(i){i.loading='lazy'})}
})();
JS;
    }
}

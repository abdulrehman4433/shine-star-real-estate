<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Mobile-only layout fixes for the Home page's stored theme CSS (the `css` column of the
 * null-slug Page row — the purchased theme's stylesheet lives per-page, not in a
 * project-wide stylesheet, so that is where these two sections' rules belong):
 *
 *  1. Hero banner search — the three controls were wrapping into a squashed two-row mess
 *     at phone width (category select and keyword input side by side, then a small Search
 *     button). They now stack full-width with a consistent 46px control height.
 *  2. "How It Works" cards — the mobile rule stacks .hiw-row vertically, but .hiw-card
 *     keeps `flex: 1 1 0` + `overflow: hidden`, so in a column flex container each card
 *     collapses to a ~76px sliver with its content clipped away. `flex: 0 0 auto` lets
 *     every card take its content height.
 *
 * The block is marker-delimited so the migration is idempotent and re-applies cleanly if
 * the Home page's CSS is ever re-pasted from the theme source (same recovery concern the
 * reviews placeholder swap documented in docs/modules/module-07-cms.md).
 */
return new class extends Migration
{
    private const BEGIN = '/* == MOBILE FIXES BEGIN == */';

    private const END = '/* == MOBILE FIXES END == */';

    private const CSS = <<<'CSS'
/* == MOBILE FIXES BEGIN == */
@media (max-width: 767.98px){
  /* HERO SEARCH — stack category / keyword / button full-width at phone size, on a solid
     white panel (the theme's default translucent/dark background read poorly over the
     banner image at phone size).
     line-height is inherited from .hero-banner (line-height:0, only there to keep the
     full-bleed image tight) and would otherwise squash the stacked controls' text. */
  .hero-banner{ line-height:normal; }
  .hero-search{
    flex-direction:column;
    align-items:stretch;
    gap:10px;
    padding:14px;
    background:#fff;
  }
  .hero-search select,
  .hero-search input{
    flex:0 0 auto;
    width:100%;
    min-width:0;
    height:46px;
    padding-top:0;
    padding-bottom:0;
    font-size:15px;
    border-color:#e6eaf1;
    border-radius:6px;
  }
  .hero-search input{
    padding-left:12px;
    padding-right:12px;
    border:1px solid #e6eaf1;
    background:#fff;
  }
  .hero-search button{
    width:100%;
    height:46px;
    padding:0;
    font-size:15px;
    font-weight:600;
  }

  /* HOW IT WORKS — .hiw-row stacks vertically here, but .hiw-card kept
     `flex:1 1 0` (a zero flex-basis) + `overflow:hidden`, which collapsed every card
     to a ~76px strip with the icon/heading/description clipped. Size to content. */
  .hiw-card{ flex:0 0 auto; }
}
/* == MOBILE FIXES END == */
CSS;

    public function up(): void
    {
        $page = Page::query()->whereNull('slug')->first();

        if (! $page) {
            return;
        }

        $css = preg_replace(
            '/' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '(\r?\n)*/s',
            '',
            (string) $page->css,
        );

        $page->update(['css' => rtrim($css) . "\n\n" . self::CSS . "\n"]);
    }

    public function down(): void
    {
        $page = Page::query()->whereNull('slug')->first();

        if (! $page) {
            return;
        }

        $css = preg_replace(
            '/' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '(\r?\n)*/s',
            '',
            (string) $page->css,
        );

        $page->update(['css' => rtrim($css) . "\n"]);
    }
};

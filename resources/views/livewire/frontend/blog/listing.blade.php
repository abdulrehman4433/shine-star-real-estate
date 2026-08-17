@push('styles')
<style>
    .blog-listing{
        --bl-navy:#1e2a78;
        --bl-navy-deep:#182161;
        --bl-blue:#3f5cf0;
        --bl-bg:#eef1fb;
        --bl-text:#1b1f3b;
        --bl-muted:#6b7093;
        --bl-shadow: 0 10px 30px rgba(30, 42, 120, 0.08);
        background:var(--bl-bg);
        color:var(--bl-text);
        padding-bottom: 1px;
    }
    .blog-listing h1, .blog-listing h2, .blog-listing h3, .blog-listing h4{
        font-weight:800;color:var(--bl-text);
    }

    .bl-header{
        background:linear-gradient(135deg, var(--bl-navy) 0%, var(--bl-navy-deep) 100%);
        padding:60px 0 50px;
    }
    .bl-header h1{color:#fff;font-size:clamp(2rem, 5vw, 3rem);letter-spacing:-1px;margin:0;}
    .bl-header-filter{min-width:220px;}
    .bl-filter-toggle{
        border-radius:30px;border:none;padding:12px 22px;font-weight:600;color:var(--bl-navy);
        background-color:#fff;width:100%;text-align:left;position:relative;
        box-shadow:0 4px 14px rgba(0,0,0,.12);transition:box-shadow .2s ease;
    }
    .bl-filter-toggle:hover{box-shadow:0 6px 18px rgba(0,0,0,.18);}
    .bl-filter-toggle::after{
        border:none;font-family:"bootstrap-icons";content:"\f282";font-size:.85rem;
        position:absolute;right:20px;top:50%;transform:translateY(-50%);transition:transform .2s ease;
        vertical-align:0;
    }
    .bl-filter-toggle.show::after{transform:translateY(-50%) rotate(180deg);}
    .bl-filter-menu{
        border:none;border-radius:14px;box-shadow:0 16px 40px rgba(24,33,97,.18);padding:10px;
        min-width:100%;margin-top:10px !important;
    }
    .bl-filter-item{
        border-radius:8px;padding:9px 14px;font-weight:500;font-size:.92rem;color:var(--bl-text);
        transition:background-color .15s ease, color .15s ease;
    }
    .bl-filter-item:hover{background-color:var(--bl-bg);color:var(--bl-navy);}
    .bl-filter-item.active{background-color:var(--bl-navy);color:#fff;}
    @media (max-width: 767.98px){
        .bl-header{padding:40px 0 32px;text-align:center;}
        .bl-header .row{row-gap:20px;}
        .bl-header-filter{justify-content:center;display:flex;margin:0 auto;}
    }

    .bl-section{padding:60px 0 50px;}
    .bl-eyebrow{text-align:center;margin-bottom:36px;}
    .bl-eyebrow h2{font-size:2rem;margin-bottom:8px;}
    .bl-eyebrow p{color:var(--bl-muted);margin-bottom:20px;}
    .bl-filter-bar{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;align-items:center;}
    .bl-filter-bar select{border-radius:30px;padding:8px 18px;border:1px solid #dfe3f5;font-size:.9rem;}
    .bl-filter-bar .btn{border-radius:30px;font-size:.85rem;}

    .bl-card{
        background:#fff;border-radius:14px;overflow:hidden;box-shadow:var(--bl-shadow);
        height:100%;display:flex;flex-direction:column;transition:transform .3s ease, box-shadow .3s ease;
    }
    .bl-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(30,42,120,0.14);}
    .bl-card .bl-thumb{height:220px;overflow:hidden;background:#e7eaf8;}
    .bl-card .bl-thumb img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;display:block;}
    .bl-card:hover .bl-thumb img{transform:scale(1.06);}
    .bl-card .bl-thumb-empty{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--bl-muted);}
    .bl-body{padding:22px 24px 24px;flex-grow:1;display:flex;flex-direction:column;}
    .bl-category{
        display:inline-block;background:#eef1fb;color:var(--bl-blue);font-weight:700;font-size:.72rem;
        text-transform:uppercase;letter-spacing:.04em;padding:4px 12px;border-radius:20px;margin-bottom:12px;
        align-self:flex-start;
    }
    .bl-meta-row{display:flex;gap:18px;color:var(--bl-muted);font-size:.85rem;margin-bottom:12px;flex-wrap:wrap;}
    .bl-meta-row span{display:flex;align-items:center;gap:6px;}
    .bl-body h3{font-size:1.1rem;line-height:1.35;margin-bottom:10px;}
    .bl-body h3 a{color:var(--bl-text);text-decoration:none;}
    .bl-body h3 a:hover{color:var(--bl-blue);}
    .bl-body p{color:var(--bl-muted);font-size:.92rem;line-height:1.6;margin-bottom:16px;flex-grow:1;}
    .bl-continue{color:var(--bl-blue);font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-style:italic;}
    .bl-continue i{transition:transform .2s ease;}
    .bl-continue:hover i{transform:translateX(4px);}

    .bl-pagination{margin-top:44px;}
    .bl-pagination nav{width:100%;row-gap:16px;}
    .bl-pagination .pagination{gap:8px;margin-bottom:0;flex-wrap:wrap;}
    .bl-pagination .page-link{
        border:none;background:#fff;color:var(--bl-text);font-weight:600;box-shadow:0 3px 10px rgba(30,42,120,0.08);
    }
    /* Desktop: numbered circular pills (the d-sm-flex block Bootstrap only shows at >=576px) */
    .bl-pagination .d-sm-flex .page-link{
        width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0;
    }
    /* Mobile: plain Previous/Next text pills (the d-sm-none block Bootstrap only shows below 576px) —
       forcing these into the same 42px circle as the desktop numbers clipped the "Previous"/"Next"
       text and broke the layout on small screens. */
    .bl-pagination .d-sm-none .page-link{
        border-radius:30px;padding:10px 20px;
    }
    .bl-pagination .page-item.active .page-link{background:var(--bl-navy);color:#fff;}
    .bl-pagination .page-item:not(.active):not(.disabled) .page-link:hover{background:var(--bl-blue);color:#fff;}
    .bl-pagination .page-item.disabled .page-link{background:transparent;box-shadow:none;color:#c3c8de;}

    .bl-cta{background:linear-gradient(120deg, var(--bl-blue), var(--bl-navy));padding:44px 0;border-radius:14px;margin-top:10px;}
    .bl-cta h3{color:#fff;font-size:1.5rem;margin-bottom:6px;}
    .bl-cta p{color:rgba(255,255,255,.85);margin-bottom:0;}
    .bl-cta-btn{background:#fff;color:var(--bl-navy);font-weight:700;border:none;padding:13px 32px;border-radius:50px;text-decoration:none;display:inline-block;white-space:nowrap;}
    .bl-cta-btn:hover{background:var(--bl-text);color:#fff;}
</style>
@endpush

<div class="blog-listing">
    <header class="bl-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1>Blog</h1>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end">
                    <div class="dropdown bl-header-filter">
                        <button type="button" class="bl-filter-toggle dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            {{ $category ? $categories->firstWhere('id', $category)?->name : 'All Categories' }}
                        </button>
                        <ul class="dropdown-menu bl-filter-menu">
                            <li>
                                <button type="button" wire:click="$set('category', '')" class="dropdown-item bl-filter-item {{ ! $category ? 'active' : '' }}">
                                    All Categories
                                </button>
                            </li>
                            @foreach ($categories as $cat)
                                <li>
                                    <button type="button" wire:click="$set('category', {{ $cat->id }})" class="dropdown-item bl-filter-item {{ (string) $category === (string) $cat->id ? 'active' : '' }}">
                                        {{ $cat->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="bl-section">
        <div class="container">
            <div class="bl-eyebrow">
                <h2>Latest News</h2>
                <p>Tips, guides, and updates from the {{ \App\Models\Setting::get('site_name', config('app.name')) }} team.</p>

                <div class="bl-filter-bar">
                    @if ($tag)
                        <span class="badge text-bg-light text-muted">
                            Tag: {{ $tags->firstWhere('id', $tag)?->name }}
                        </span>
                    @endif
                    @if ($category || $tag)
                        <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Clear filters
                        </button>
                    @endif
                </div>
            </div>

            @if ($posts->isEmpty())
                <div class="alert alert-info text-center">No blog posts match your filters yet.</div>
            @else
                <div class="row g-4">
                    @foreach ($posts as $post)
                        <div class="col-md-6 col-lg-4">
                            <div class="bl-card">
                                <a href="{{ route('blog.show', $post) }}" class="bl-thumb">
                                    @if ($post->featured_thumb_url)
                                        <img src="{{ $post->featured_thumb_url }}" alt="{{ $post->title }}">
                                    @else
                                        <div class="bl-thumb-empty">No Image</div>
                                    @endif
                                </a>
                                <div class="bl-body">
                                    @if ($post->category)
                                        <span class="bl-category">{{ $post->category->name }}</span>
                                    @endif
                                    <div class="bl-meta-row">
                                        <span><i class="bi bi-calendar3"></i> {{ $post->published_at?->format('M j, Y') }}</span>
                                        <span><i class="bi bi-person"></i> {{ $post->author->name }}</span>
                                    </div>
                                    <h3><a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h3>
                                    @if ($post->excerpt)
                                        <p>{{ $post->excerpt }}</p>
                                    @endif
                                    <a href="{{ route('blog.show', $post) }}" class="bl-continue">
                                        Continue <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($posts->hasPages())
                    <div class="bl-pagination">
                        {{ $posts->links() }}
                    </div>
                @endif
            @endif

            <div class="bl-cta mt-5 px-4 px-md-5">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3>Want To Become A Real Estate Agent?</h3>
                        <p>We'll help you to grow your career and growth.</p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-md-end mt-3 mt-md-0">
                        <a href="{{ route('register') }}" class="bl-cta-btn">SignUp Today</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

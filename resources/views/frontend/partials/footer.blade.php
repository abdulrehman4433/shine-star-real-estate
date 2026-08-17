<style>
    .ssm-footer{
        background: linear-gradient(to right, #012051, #001f51);
        color: rgba(255,255,255,0.85);
        padding-top: 2rem;
        padding-bottom: 1rem;
    }
    .ssm-footer__title{
        color: #ffffff;
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 0.75rem;
    }
    .ssm-footer__text{
        color: rgba(255,255,255,0.7);
        font-size: 0.9rem;
        line-height: 1.6;
        white-space: pre-line;
    }
    .ssm-footer__links{
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .ssm-footer__links li{
        margin-bottom: 0.4rem;
    }
    .ssm-footer__link{
        color: rgba(255,255,255,0.75);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.2s ease, padding-left 0.2s ease;
        display: inline-block;
    }
    .ssm-footer__link:hover{
        color: #ffffff;
        padding-left: 4px;
    }
    .ssm-footer__hr{
        margin: 1rem 0;
    }
    .ssm-footer__bottom{
        padding: 0.75rem 0;
    }
    .ssm-footer__brand{
        color: #ffffff;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    .ssm-footer__tagline{
        color: rgba(255,255,255,0.6);
        font-size: 0.85rem;
        margin-bottom: 0;
    }
    .ssm-footer__copyright{
        color: rgba(255,255,255,0.6);
        font-size: 0.85rem;
        margin-bottom: 0;
    }
    .ssm-footer__social-link{
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        margin-right: 1rem;
        transition: color 0.2s ease;
    }
    .ssm-footer__social-link:hover{
        color: #ffffff;
    }
</style>
<footer class="ssm-footer mt-auto">
    <div class="container">
        @if ($footerColumns->isNotEmpty() && $footerSetting && $footerSetting->isActive())
            <div class="row mb-4">
                @for ($col = 0; $col < $footerSetting->columns; $col++)
                    @php $widgets = $footerColumns->get($col); @endphp
                    @if ($widgets)
                        <div class="col-md-{{ 12 / $footerSetting->columns }} mb-3">
                            @foreach ($widgets as $widget)
                                <div class="mb-3">
                                    @if ($widget->title)
                                        <h6 class="ssm-footer__title">{{ $widget->title }}</h6>
                                    @endif

                                    @if ($widget->type === 'text')
                                        <p class="ssm-footer__text">{{ $widget->content['body'] ?? '' }}</p>
                                    @else
                                        <ul class="ssm-footer__links">
                                            @foreach ($widget->content['links'] ?? [] as $link)
                                                <li><a href="{{ $link['url'] ?? '#' }}" class="ssm-footer__link">{{ $link['label'] ?? '' }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endfor
            </div>
            <hr class="ssm-footer__hr">
        @endif

        @php
            $bottomCompanyName = ($footerSetting && $footerSetting->company_name) ? $footerSetting->company_name : $siteName;
            $bottomSocialLinks = collect($footerSetting->social_links ?? []);

            $showCompany = $footerSetting->show_company_name ?? true;
            $showTagline = $footerSetting->show_tagline ?? true;
            $showSocial = $footerSetting->show_social_links ?? true;
            $showCopyright = $footerSetting->show_copyright ?? true;

            $posCompany = $footerSetting->position_company_name ?? 'left';
            $posTagline = $footerSetting->position_tagline ?? 'left';
            $posSocial = $footerSetting->position_social_links ?? 'right';
            $posCopyright = $footerSetting->position_copyright ?? 'right';

            // Build left and right content arrays
            $leftItems = [];
            $rightItems = [];

            if ($showCompany) {
                $item = '<h6 class="ssm-footer__brand mb-0">'.e($bottomCompanyName).'</h6>';
                $posCompany === 'left' ? $leftItems[] = $item : $rightItems[] = $item;
            }
            if ($showTagline) {
                $tagline = ($footerSetting && $footerSetting->copyright_tagline)
                    ? $footerSetting->copyright_tagline
                    : 'Find your next home, plot, or commercial space.';
                $item = '<p class="ssm-footer__tagline mt-1">'.e($tagline).'</p>';
                $posTagline === 'left' ? $leftItems[] = $item : $rightItems[] = $item;
            }
            if ($showSocial && $bottomSocialLinks->isNotEmpty()) {
                $socialHtml = '<div class="mb-1">';
                foreach ($bottomSocialLinks as $link) {
                    $lnk = is_array($link) ? (object) $link : $link;
                    $url = e($lnk->url ?? '#', ENT_NOQUOTES);
                    $icon = e($lnk->icon ?? 'bi bi-link-45deg', ENT_NOQUOTES);
                    $title = e($lnk->platform ?? '');
                    $socialHtml .= '<a href="'.$url.'" target="_blank" rel="noopener" class="ssm-footer__social-link" title="'.$title.'"><i class="'.$icon.'" style="font-size:1.1rem;"></i></a>';
                }
                $socialHtml .= '</div>';
                $posSocial === 'left' ? $leftItems[] = $socialHtml : $rightItems[] = $socialHtml;
            }
            if ($showCopyright) {
                $copyright = ($footerSetting && $footerSetting->copyright_text)
                    ? $footerSetting->copyright_text
                    : '&copy; '.date('Y').' '.$siteName.'. All rights reserved.';
                $item = '<p class="ssm-footer__copyright mb-0">'.$copyright.'</p>';
                $posCopyright === 'left' ? $leftItems[] = $item : $rightItems[] = $item;
            }

            $hasLeft = !empty($leftItems);
            $hasRight = !empty($rightItems);
        @endphp

        @if ($hasLeft || $hasRight)
            <div class="ssm-footer__bottom">
                <div class="row align-items-center">
                    @if ($hasLeft)
                        <div class="{{ $hasRight ? 'col-md-6' : 'col-md-12' }}">
                            @foreach ($leftItems as $item)
                                {!! $item !!}
                            @endforeach
                        </div>
                    @endif
                    @if ($hasRight)
                        <div class="{{ $hasLeft ? 'col-md-6 text-md-end' : 'col-md-12 text-center' }}">
                            @foreach ($rightItems as $item)
                                {!! $item !!}
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</footer>

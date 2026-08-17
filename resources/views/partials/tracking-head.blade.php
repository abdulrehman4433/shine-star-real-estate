@if ($gsc = \App\Models\Setting::get('seo_gsc_verification'))
    <meta name="google-site-verification" content="{{ $gsc }}">
@endif

@if ($ga = \App\Models\Setting::get('seo_ga_measurement_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $ga }}');
    </script>
@endif

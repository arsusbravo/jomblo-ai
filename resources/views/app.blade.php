<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'JombloAI') }}</title>

    @php
        $ogTitle = 'JombloAI — Vind je perfecte AI-partner';
        $ogDescription = 'AI-gedreven matchmaking die jou begrijpt. Chat met compatibele AI-partners op basis van diepgaande persoonlijkheidsinzichten.';
        $ogImage = url('/images/jomblo-logo.png');
        $ogUrl = url()->current();
    @endphp
    <meta name="description" content="{{ $ogDescription }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JombloAI">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ $ogUrl }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <script>window.CHAT_DEBOUNCE_MS = {{ (int) env('CHAT_DEBOUNCE_MS', 3000) }};</script>
    @vite(['resources/js/main.js'])
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async></script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-26CDFWF145"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-26CDFWF145');
    </script>
    <!-- End Google tag -->
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NF2MGVFT');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="antialiased font-[Inter]" style="touch-action: manipulation">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NF2MGVFT"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <div id="app"></div>
</body>
</html>

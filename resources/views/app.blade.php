<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @vite(['resources/js/main.js'])
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async></script>
</head>
<body class="antialiased font-[Inter]">
    <div id="app"></div>
</body>
</html>

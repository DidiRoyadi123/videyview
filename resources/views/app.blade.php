<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'VideyView') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <!-- SEO Pro: OpenGraph / Dynamic Meta -->
        @if(isset($page['props']['seo']))
            <meta name="description" content="{{ $page['props']['seo']['description'] }}">
            <meta property="og:title" content="{{ $page['props']['seo']['title'] }}">
            <meta property="og:description" content="{{ $page['props']['seo']['description'] }}">
            <meta property="og:image" content="{{ $page['props']['seo']['image'] }}">
            <meta property="og:type" content="video.other">
            <meta property="og:url" content="{{ url()->current() }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#4f46e5">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

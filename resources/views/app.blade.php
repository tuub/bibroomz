<!DOCTYPE html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base-url" content="{{ url('/') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#fafafa">
    @vite('resources/sass/main.scss')
    @inertiaHead
</head>
<noscript>
    The Roomz application needs JavaScript enabled to work. Please enable it to continue.
</noscript>

<body>
    @inertia
</body>

@vite('resources/js/app.js')
</html>

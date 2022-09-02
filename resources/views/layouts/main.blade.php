<!DOCTYPE html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <title>{{ env('APP_NAME') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base-url" content="{{ url('/') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{!! url('css/app.css') !!}">
    <meta name="theme-color" content="#fafafa">
    @vite('resources/css/app.css')
</head>

<body>
<noscript>
    The Roomz application needs JavaScript enabled to work. Please enable it to continue.
</noscript>

<div id="app">
    @yield('content')
</div>
@vite('resources/js/app.js')
</body>
</html>

<!DOCTYPE html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base-url" content="{{ url('/') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,400;1,700&display=swap"
          rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <meta name="theme-color" content="#fafafa">
    @vite('resources/sass/main.scss')
</head>
<noscript>
    The Roomz application needs JavaScript enabled to work. Please enable it to continue.
</noscript>

<body>

<div id="app"></div>

</body>

@vite('resources/js/admin.js')
</html>

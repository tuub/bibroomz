<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base-url" content="{{ url('/') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="">
    @vite('resources/sass/main.scss')
    <script>
        (function () {
            try {
                var raw = localStorage.getItem('theme');
                var preference = raw ? (JSON.parse(raw).preference || 'system') : 'system';
                var isDark = preference === 'dark'
                    || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                var resolvedTheme = isDark ? 'dark' : 'light';
                var themeColor = document.querySelector('meta[name="theme-color"]');

                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.style.colorScheme = resolvedTheme;

                var appPageColor = window.getComputedStyle(document.documentElement)
                    .getPropertyValue('--color-app-page')
                    .trim();

                if (themeColor && appPageColor) {
                    themeColor.setAttribute('content', 'rgb(' + appPageColor.replace(/\s+/g, ', ') + ')');
                }
            } catch (e) {}
        })();
    </script>
    @inertiaHead
</head>
<body class="bg-app-page dark:bg-app-page">
    @inertia
</body>
@vite('resources/js/app.ts')
</html>

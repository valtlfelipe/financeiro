<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-appearance="{{ $appearance ?? 'system' }}" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#148A62">
        <meta name="description" content="{{ __('app.meta.description') }}">
        <link rel="manifest" href="/manifest.webmanifest">

        <script>
            (() => {
                let appearance = document.documentElement.dataset.appearance;
                try {
                    const saved = localStorage.getItem('appearance');
                    if (['light', 'dark', 'system'].includes(saved)) appearance = saved;
                } catch {}
                document.documentElement.classList.toggle('dark',
                    appearance === 'dark' ||
                    (appearance !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            })();
        </script>
        <style>
            html {
                background-color: #f3f5f2;
                color-scheme: light;
            }
            html.dark {
                background-color: #17201b;
                color-scheme: dark;
            }
        </style>

        <link rel="icon" href="/favicon.ico?v=2" sizes="16x16 32x32 48x48">
        <link rel="icon" href="/icons/financeiro-64.png" type="image/png" sizes="64x64">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2" sizes="180x180">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Financeiro') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

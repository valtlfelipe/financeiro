<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#148A62">
        <meta name="description" content="{{ __('app.meta.description') }}">
        <link rel="manifest" href="/manifest.webmanifest">

        {{-- The v1 visual language is intentionally light-first. Keep the root
             background stable before the app bundle loads as well. --}}
        <style>
            html {
                background-color: #f3f5f2;
                color-scheme: light;
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

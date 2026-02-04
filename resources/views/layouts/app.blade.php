<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('app.direction') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body>
        {{ $slot }}

        @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>

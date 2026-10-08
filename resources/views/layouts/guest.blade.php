<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Sistem Cuti') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-blue-100 via-white to-blue-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/">
                <img src="{{ asset('images/logo-fss.png') }}" class="w-16 h-16" alt="Logo PT FSS" />
            </a>
        </div>

        <div
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 100)"
            x-show="show"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-8"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white/90 backdrop-blur-md shadow-xl rounded-xl border border-gray-200"
        >
            {{ $slot }}

            <p class="text-center text-xs text-gray-500 mt-6">
                © {{ now()->year }} PT FSS – Sistem Pengajuan Cuti
            </p>
        </div>
    </div>
</body>
</html>

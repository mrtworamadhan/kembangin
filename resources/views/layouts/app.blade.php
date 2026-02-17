<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Masuk - Kembangin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 min-h-screen flex items-center justify-center">
    
    <main class="w-full max-w-md p-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
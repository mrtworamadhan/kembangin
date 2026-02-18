<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Kembangin</title>

    <meta name="description" content="{{ $description ?? 'Aplikasi cerdas untuk manajemen kekayaan keluarga, pencatatan bisnis, dan pengaturan anggaran rutin secara real-time.' }}">
    <meta name="keywords" content="Keuangan Keluarga, Manajemen Aset, PWA Keuangan, Pencatatan Bisnis, Kembangin, Budgeting, Wealth Management">
    <meta name="author" content="Kembangin">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/icon-colour.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#16a34a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kembangin">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-colour.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'Kembangin - Family Wealth' }}">
    <meta property="og:description" content="{{ $description ?? 'Pantau kas free, atur prioritas anggaran, dan capai impian finansial keluargamu dengan mudah.' }}">
    <meta property="og:image" content="{{ asset('images/brand/icon-colour.png') }}">
    <meta property="og:site_name" content="Kembangin">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Kembangin - Family Wealth' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Pantau kas free, atur prioritas anggaran, dan capai impian finansial keluargamu dengan mudah.' }}">
    <meta name="twitter:image" content="{{ asset('images/brand/icon-colour.png') }}">

    <link rel="icon" type="image/png" href="{{ asset('images/brand/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased font-sans selection:bg-green-500 selection:text-white">

    <div 
        x-data="{ 
            darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        }"
        x-init="
            $watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'));
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) darkMode = e.matches;
            });
        "
        :class="{ 'dark': darkMode }"
    >
        
        <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 flex items-center justify-center p-6 w-full transition-colors duration-300 relative overflow-hidden">
            
            <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
                <div class="absolute -bottom-10 -right-10 w-64 h-64 opacity-15 dark:opacity-10 transition-all duration-500 transform -rotate-12">
                    <img src="{{ asset('images/brand/icon-colour.png') }}" class="w-full h-full object-contain dark:hidden">
                    <img src="{{ asset('images/brand/icon-white.png') }}" class="w-full h-full object-contain hidden dark:block">
                </div>
            </div>

            <a href="/" class="absolute top-6 left-6 p-2 bg-white dark:bg-zinc-800 rounded-full shadow-sm border border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:text-green-600 dark:hover:text-green-400 transition hover:scale-105 z-50">
                <x-heroicon-o-arrow-left class="w-5 h-5" />
            </a>

            <main class="relative z-10 w-full max-w-md">
                {{ $slot }}
            </main>

        </div>

    </div>

    @livewireScripts
</body>
</html>

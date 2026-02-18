<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Kembangin' }}</title>

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style> 
        [x-cloak] { display: none !important; } 
        body::-webkit-scrollbar {
            display: none;
        }

        body {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    @if(config('services.ga.measurement_id') || env('GA_MEASUREMENT_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <style> 
            [x-cloak] { display: none !important; } 
            
            /* Libas habis scrollbar di SEMUA elemen untuk Chrome, Safari, Edge, Opera */
            *::-webkit-scrollbar {
                display: none !important;
            }

            /* Libas habis scrollbar untuk Firefox & IE */
            * {
                -ms-overflow-style: none !important;
                scrollbar-width: none !important;
            }
        </style>
    @endif
</head>
<body class="antialiased bg-zinc-50 dark:bg-zinc-900 transition-colors duration-300">

    <div 
        x-data="{ darkMode: localStorage.getItem('theme') === 'dark', showProfileMenu: false }"
        x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
        :class="{ 'dark': darkMode }"
    >
        
        <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 font-sans pb-24 relative">
            <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
                <div class="absolute bottom-15 -left-20 w-64 h-64 opacity-15 dark:opacity-10 transform transition-all duration-500">
                    
                    <img src="{{ asset('images/brand/icon-colour.png') }}" alt="Watermark" class="w-full h-full object-contain dark:hidden">
                    
                    <img src="{{ asset('images/brand/icon-white.png') }}" alt="Watermark" class="w-full h-full object-contain hidden dark:block">
                    
                </div>
            </div>
            <div class="absolute top-4 right-4 z-40 flex items-center gap-2">
                
                <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm rounded-full p-1 flex items-center gap-1">
                    
                    <button @click="darkMode = !darkMode" class="w-8 h-8 rounded-full flex items-center justify-center text-zinc-500 hover:text-green-600 dark:text-zinc-400 dark:hover:text-green-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition focus:outline-none">
                        <x-heroicon-o-moon x-show="!darkMode" class="w-4 h-4" />
                        <x-heroicon-o-sun x-show="darkMode" class="w-4 h-4" x-cloak />
                    </button>
                    
                    <div class="relative">
                        <button @click="showProfileMenu = !showProfileMenu" @click.away="showProfileMenu = false" class="w-8 h-8 rounded-full bg-green-600 dark:bg-green-700 text-white flex items-center justify-center text-xs font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500/50 transition transform hover:scale-105">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </button>

                        <div 
                            x-show="showProfileMenu" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                            class="absolute right-0 top-10 mt-2 w-56 bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-100 dark:border-zinc-700 overflow-hidden z-50 origin-top-right"
                            x-cloak
                        >
                            <div class="p-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 truncate">{{ auth()->user()->name ?? 'User' }}</p>
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            
                            <div class="p-2">
                                <a href="{{ route('app.profile') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                                    <x-heroicon-o-user class="w-5 h-5 text-zinc-400" /> Profil Saya
                                </a>
                                <a href="{{ url('/tenant') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                                    <x-heroicon-o-circle-stack class="w-5 h-5 text-zinc-400" /> Panel Bisnis
                                </a>
                                <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                                    <x-heroicon-o-cog-8-tooth class="w-5 h-5 text-zinc-400" /> Pengaturan
                                </a>
                            </div>
                            
                            <div class="p-2 border-t border-zinc-100 dark:border-zinc-700">
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf
                                    <button type="submit" @click.prevent="$root.submit();" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition">
                                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" /> Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main class="p-4 max-w-md mx-auto w-full min-h-[calc(100vh-140px)]">
                {{ $slot }}
            </main>

            <div class="fixed bottom-0 left-0 w-full bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-end px-2 sm:px-4 pb-safe pt-2 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] dark:shadow-none z-40 transition-colors duration-300">
                
                <a href="{{ route('app.home') }}" wire:navigate class="flex flex-col items-center p-2 w-14 transition {{ request()->routeIs('pwa.home') ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    @if(request()->routeIs('pwa.home'))
                        <x-heroicon-s-home class="w-6 h-6 mb-1" />
                    @else
                        <x-heroicon-o-home class="w-6 h-6 mb-1" />
                    @endif
                    <span class="text-[10px] font-medium">Home</span>
                </a>

                <a href="{{ route('app.analytics') }}" wire:navigate class="flex flex-col items-center p-2 w-14 transition {{ request()->routeIs('app.analytics') ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    @if(request()->routeIs('app.analytics'))
                        <x-heroicon-s-chart-pie class="w-6 h-6 mb-1" />
                    @else
                        <x-heroicon-o-chart-pie class="w-6 h-6 mb-1" />
                    @endif
                    <span class="text-[10px] font-medium">Analisa</span>
                </a>

                <div class="relative -top-5 w-14 flex justify-center">
                    <a href="{{ route('app.transaction') }}" wire:navigate class="flex items-center justify-center w-[52px] h-[52px] bg-gradient-to-br from-green-600 to-teal-600 text-white rounded-full shadow-lg hover:bg-green-700 dark:hover:bg-green-400 transition transform hover:scale-105 border-4 border-zinc-50 dark:border-zinc-900 focus:outline-none">
                        <x-heroicon-o-arrows-right-left class="w-6 h-6" />
                    </a>
                </div>

                <a href="{{ route('app.assets') }}" wire:navigate class="flex flex-col items-center p-2 w-14 transition {{ request()->routeIs('app.assets') ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    @if(request()->routeIs('app.assets'))
                        <x-heroicon-s-wallet class="w-6 h-6 mb-1" />
                    @else
                        <x-heroicon-o-wallet class="w-6 h-6 mb-1" />
                    @endif
                    <span class="text-[10px] font-medium">Assets</span>
                </a>

                <a href="{{ route('app.ledger') }}" wire:navigate class="flex flex-col items-center p-2 w-14 transition {{ request()->routeIs('app.ledger') ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    @if(request()->routeIs('app.ledger'))
                        <x-heroicon-s-document-text class="w-6 h-6 mb-1" />
                    @else
                        <x-heroicon-o-document-text class="w-6 h-6 mb-1" />
                    @endif
                    <span class="text-[10px] font-medium">Mutasi</span>
                </a>

            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
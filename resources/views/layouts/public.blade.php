<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    <title>{{ $title ?? 'Kembangin - Usaha Berkembang, Keluarga Tenang' }}</title>

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
    @if(config('services.ga.measurement_id') || env('GA_MEASUREMENT_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <style> 
            [x-cloak] { display: none !important; } 
            
        </style>
    @endif
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function (reg) {
                    console.log('SW REGISTERED with scope:', reg.scope);
                })
                .catch(function (err) {
                    console.error('SW FAILED:', err);
                });
        });
    }
    </script>
</head>
<body class="antialiased bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 font-sans selection:bg-green-500 selection:text-white">
    <div 
        x-data="{ darkMode: localStorage.getItem('theme') === 'dark', mobileMenuOpen: false }"
        x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
        :class="{ 'dark': darkMode }"
    >
        <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 font-sans transition-colors duration-300">
            <nav class="fixed top-0 w-full bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">

                        <a href="/" class="flex items-center group">
                            <img 
                                src="{{ asset('images/brand/logo.png') }}" 
                                alt="Kembangin"
                                class="h-9 w-auto transition-transform duration-300 group-hover:scale-105"
                            >
                        </a>

                        <div class="hidden md:flex items-center gap-4">
                            <a href="{{ route('docs') }}" class="text-sm font-bold text-zinc-600 dark:text-zinc-300 hover:text-green-600 dark:hover:text-green-400 transition">
                                Panduan
                            </a>

                            <button @click="darkMode = !darkMode" class="p-2 rounded-full text-zinc-500 hover:text-green-600 dark:text-zinc-400 dark:hover:text-green-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition focus:outline-none">
                                <x-heroicon-o-moon x-show="!darkMode" class="w-5 h-5" />
                                <x-heroicon-o-sun x-show="darkMode" class="w-5 h-5" x-cloak />
                            </button>
                            
                            @auth
                                <a href="{{ route('app.home') }}" wire:navigate class="px-6 py-2 bg-green-600 text-white rounded-xl font-bold">
                                    Kembali ke Aplikasi
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="px-6 py-2 border border-zinc-200 rounded-xl font-bold">
                                    Masuk
                                </a>

                                <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-full shadow-lg shadow-green-600/20 transition-all duration-300 hover:scale-105">
                                    Daftar Gratis
                                </a>
                            @endauth
                        </div>

                        <div class="flex items-center gap-2 md:hidden">
                            <button @click="darkMode = !darkMode" class="p-2 rounded-full text-zinc-500 hover:text-green-600 dark:text-zinc-400 dark:hover:text-green-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition focus:outline-none">
                                <x-heroicon-o-moon x-show="!darkMode" class="w-5 h-5" />
                                <x-heroicon-o-sun x-show="darkMode" class="w-5 h-5" x-cloak />
                            </button>

                            <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-zinc-600 dark:text-zinc-300 focus:outline-none">
                                <x-heroicon-o-bars-3 x-show="!mobileMenuOpen" class="w-6 h-6" />
                                <x-heroicon-o-x-mark x-show="mobileMenuOpen" class="w-6 h-6" x-cloak />
                            </button>
                        </div>

                    </div>
                </div>

                <div x-show="mobileMenuOpen" x-collapse class="md:hidden bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="px-4 pt-2 pb-4 space-y-2">
                        <a href="{{ route('docs') }}" class="block px-3 py-2 text-base font-bold text-zinc-800 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md">
                            Panduan
                        </a>
                        @auth
                        <a href="{{ route('app.home') }}" class="block mt-2 px-3 py-3 text-center text-base font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            Kembali Ke Aplikasi
                        </a>
                        @else
                        <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-bold text-zinc-800 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-md">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="block mt-2 px-3 py-3 text-center text-base font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            Daftar Gratis
                        </a>
                        @endauth
                        
                        
                    </div>
                </div>
            </nav>

            <main class="pt-16 min-h-screen">
                {{ $slot }}
            </main>

            <footer class="bg-white dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 py-10 mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="flex justify-center mb-4">
                        <img 
                            src="{{ asset('images/brand/logo.png') }}" 
                            alt="Kembangin"
                            class="h-9 w-auto grayscale hover:grayscale-0 transition duration-300"
                        >
                    </div>
                    <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-2">Usaha Berkembang, Keluarga Tenang.</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">&copy; {{ date('Y') }} Kembangin Family Wealth. All rights reserved.</p>
                </div>
            </footer>

        </div>
    </div>
    
    @livewireScripts
    <div x-data="{
            showBanner: false,
            deferredPrompt: null,
            init() {
                setTimeout(() => { 
                    if(!localStorage.getItem('hideInstall')) this.showBanner = true; 
                }, 2000);

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if(!localStorage.getItem('hideInstall')) this.showBanner = true;
                });
            },
            installApp() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    this.deferredPrompt.userChoice.then((choice) => {
                        if (choice.outcome === 'accepted') {
                            this.closeBanner();
                        }
                        this.deferredPrompt = null;
                    });
                } else {
                    alert('Untuk install manual: Ketuk menu browser (titik tiga atau tombol Share) lalu pilih Add to Home Screen / Tambahkan ke Layar Utama.');
                }
            },
            closeBanner() {
                this.showBanner = false;
                localStorage.setItem('hideInstall', 'true');
            }
        }"
        x-show="showBanner"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="fixed top-0 left-0 w-full z-[100] bg-green-600 text-white shadow-xl border-b border-green-500/50"
        style="display: none;"
        x-cloak
    >
        <div class="px-4 py-3 flex items-center justify-between max-w-md mx-auto pt-safe">
            <div class="flex items-center gap-3">
                <div class="bg-white p-1 rounded-xl shadow-inner">
                    <img src="{{ asset('images/brand/icon-colour.png') }}" alt="Logo" class="w-8 h-8 rounded-lg">
                </div>
                <div>
                    <p class="text-sm font-extrabold leading-tight tracking-wide">Install Kembangin</p>
                    <p class="text-[10px] text-green-100 font-medium">Akses secepat kilat!</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button @click="installApp()" class="px-5 py-2 bg-white text-green-700 hover:bg-green-50 text-[11px] sm:text-xs font-extrabold rounded-full shadow-md transition transform hover:scale-105 whitespace-nowrap min-w-[80px] text-center">
                    Install
                </button>
                <button @click="closeBanner()" class="text-green-200 hover:text-white transition p-1.5 rounded-full hover:bg-green-700 shrink-0">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</body>
</html>
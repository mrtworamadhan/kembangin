<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::public')] class extends Component {
    // Logic bisa ditambah nanti jika butuh form newsletter dsb.
};
?>

<div>
    <section class="relative pt-24 pb-32 overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-full bg-green-50 dark:bg-green-900/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute top-20 right-10 w-64 h-64 bg-orange-400/20 dark:bg-orange-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold mb-6 border border-green-200 dark:border-green-800/50 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Akses Beta Terbatas Dibuka
            </div>
            
            <h1 class="text-5xl md:text-7xl font-extrabold text-zinc-900 dark:text-white tracking-tight mb-6 animate-fade-in-up" style="animation-delay: 100ms;">
                Usaha <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-500 to-green-700">Berkembang</span>, <br class="hidden md:block">
                Keluarga <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">Tenang</span>.
            </h1>
            
            <p class="mt-4 max-w-2xl text-lg md:text-xl text-zinc-600 dark:text-zinc-400 mx-auto mb-10 animate-fade-in-up" style="animation-delay: 200ms;">
                Satu aplikasi cerdas untuk memisahkan uang dapur dan uang toko. Pantau kas bebas, atur anggaran, dan wujudkan impian finansial tanpa stres.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 animate-fade-in-up" style="animation-delay: 300ms;">
                <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 text-base font-extrabold text-white bg-green-600 hover:bg-green-700 rounded-full shadow-xl shadow-green-600/30 transition hover:scale-105 flex items-center justify-center gap-2">
                    Mulai Sekarang (Gratis) <x-heroicon-o-arrow-right class="w-5 h-5" />
                </a>
                <a href="#fitur" class="w-full sm:w-auto px-8 py-4 text-base font-bold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-full shadow-sm transition flex items-center justify-center gap-2">
                    Lihat Fitur <x-heroicon-o-chevron-down class="w-5 h-5" />
                </a>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-20 bg-white dark:bg-zinc-900 border-t border-zinc-100 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white mb-4">Berhenti Merasa "Bocor Alus"</h2>
                <p class="text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">Kami merancang arsitektur keuangan tingkat enterprise ke dalam antarmuka PWA yang sesederhana aplikasi chat Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-3xl border border-zinc-100 dark:border-zinc-700 hover:border-green-500 dark:hover:border-green-500 transition-colors group">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-check-badge class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Kas Free Pintar</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Sistem otomatis memisahkan uang fisik Anda dengan uang tabungan. Ketahui persis berapa "Uang Jajan" yang benar-benar bisa Anda pakai hari ini.</p>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-3xl border border-zinc-100 dark:border-zinc-700 hover:border-orange-500 dark:hover:border-orange-500 transition-colors group">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-presentation-chart-line class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Evaluasi Bisnis</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Hubungkan data toko Anda. Pantau estimasi profit, hutang supplier, piutang, dan status kesehatan bisnis (Likuiditas) secara real-time.</p>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-3xl border border-zinc-100 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 transition-colors group">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-star class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Fokus Impian</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Buat target tabungan (Goals) seperti KPR, Umroh, atau Dana Darurat. Pantau progress bar-nya langsung dari dashboard depan.</p>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/50 rounded-3xl border border-zinc-100 dark:border-zinc-700 hover:border-red-500 dark:hover:border-red-500 transition-colors group">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-fire class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Cegat Konsumtif</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Bukan sekadar buku kas. Algoritma kami memecah pengeluaran Anda jadi Produktif (Aset) vs Konsumtif (Hangus) lengkap dengan Top 5 Kebocoran.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 relative overflow-hidden z-0">
        <div class="absolute inset-0 bg-green-600 dark:bg-green-800 transform -skew-y-2 origin-top-left"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white relative z-10 pt-10 pb-4">
            <h2 class="text-3xl md:text-5xl font-extrabold mb-6">Siap Merapikan Keuangan Anda?</h2>
            <p class="text-green-100 text-lg mb-10">Bergabunglah dengan program Beta Eksklusif kami hari ini. Gratis tanpa syarat.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-4 text-base font-extrabold text-green-700 bg-white hover:bg-zinc-50 rounded-full shadow-2xl transition hover:scale-105">
                Daftar & Request Akses <x-heroicon-o-lock-open class="w-5 h-5" />
            </a>
        </div>
    </section>
</div>
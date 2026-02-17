<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::public')] class extends Component {
    // Halaman statis untuk FAQ & Panduan
};
?>

<div class="pt-10 pb-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center mb-16 animate-fade-in-up">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm transform -rotate-3">
            <x-heroicon-o-book-open class="w-8 h-8 rotate-3" />
        </div>
        <h1 class="text-3xl md:text-5xl font-extrabold text-zinc-900 dark:text-white tracking-tight mb-4">
            Pusat Bantuan & <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-500 to-green-700">Panduan</span>
        </h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-400">
            Pahami cara kerja Kembangin agar keuangan keluarga dan bisnismu maksimal.
        </p>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 animate-fade-in-up" style="animation-delay: 150ms;">
        
        <div x-data="{ activeAccordion: 1 }" class="space-y-4">
            
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-3xl overflow-hidden shadow-sm transition-colors hover:border-green-500 dark:hover:border-green-500">
                <button @click="activeAccordion = activeAccordion === 1 ? null : 1" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center text-sm shrink-0">1</span>
                        Apa itu "Kas Free" (Uang Jajan)?
                    </h3>
                    <x-heroicon-o-chevron-down class="w-5 h-5 text-zinc-400 transition-transform duration-300" x-bind:class="activeAccordion === 1 ? 'rotate-180 text-green-500' : ''" />
                </button>
                <div x-show="activeAccordion === 1" x-collapse x-transition class="px-6 pb-6 text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
                    <p><strong>Kas Free</strong> adalah sisa uang fisik Anda yang <strong>benar-benar boleh dipakai</strong> untuk kebutuhan sehari-hari atau jajan. Sistem menghitungnya dengan cara: <br><br>
                    <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-green-600 dark:text-green-400">Total Uang di Dompet - Total Uang yang Dikunci (Tabungan) = Kas Free.</code><br><br>
                    Dengan melihat Kas Free, Anda tidak akan merasa "kaya palsu" karena uang yang dialokasikan untuk tabungan sudah diamankan oleh sistem.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-3xl overflow-hidden shadow-sm transition-colors hover:border-green-500 dark:hover:border-green-500">
                <button @click="activeAccordion = activeAccordion === 2 ? null : 2" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 flex items-center justify-center text-sm shrink-0">2</span>
                        Bagaimana memisahkan Uang Dapur & Uang Bisnis?
                    </h3>
                    <x-heroicon-o-chevron-down class="w-5 h-5 text-zinc-400 transition-transform duration-300" x-bind:class="activeAccordion === 2 ? 'rotate-180 text-orange-500' : ''" />
                </button>
                <div x-show="activeAccordion === 2" x-collapse x-transition class="px-6 pb-6 text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
                    <p>Di Kembangin, setiap anggota keluarga memiliki "Panel Personal" (Untuk Dapur) dan "Panel Bisnis" (Untuk Toko). 
                    <br><br>
                    Jika Anda ingin mencatat pengeluaran untuk makan atau tagihan listrik rumah, catat di <strong>Dashboard Rumah Tangga</strong>. Jika Anda membeli stok barang untuk dijual lagi, masuklah ke <strong>Dashboard Bisnis</strong>. Sistem analitik kami akan otomatis memisahkannya agar uang Anda tidak tercampur.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-3xl overflow-hidden shadow-sm transition-colors hover:border-green-500 dark:hover:border-green-500">
                <button @click="activeAccordion = activeAccordion === 3 ? null : 3" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm shrink-0">3</span>
                        Saya sudah daftar, tapi kenapa dilempar ke Ruang Tunggu?
                    </h3>
                    <x-heroicon-o-chevron-down class="w-5 h-5 text-zinc-400 transition-transform duration-300" x-bind:class="activeAccordion === 3 ? 'rotate-180 text-blue-500' : ''" />
                </button>
                <div x-show="activeAccordion === 3" x-collapse x-transition class="px-6 pb-6 text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
                    <p>Kembangin saat ini berada dalam fase <strong>Private Beta</strong> untuk menjaga eksklusivitas dan kualitas layanan. Setiap pendaftar baru harus melalui verifikasi manual (Approval) oleh Admin. <br><br>
                    Untuk mempercepat proses aktivasi, silakan klik tombol <strong>"Chat Admin"</strong> di halaman ruang tunggu, dan kami akan mengaktifkan akun Anda secara instan!</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-3xl overflow-hidden shadow-sm transition-colors hover:border-green-500 dark:hover:border-green-500">
                <button @click="activeAccordion = activeAccordion === 4 ? null : 4" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                    <h3 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center text-sm shrink-0">4</span>
                        Apa itu "Pengeluaran Hangus" (Konsumtif)?
                    </h3>
                    <x-heroicon-o-chevron-down class="w-5 h-5 text-zinc-400 transition-transform duration-300" x-bind:class="activeAccordion === 4 ? 'rotate-180 text-red-500' : ''" />
                </button>
                <div x-show="activeAccordion === 4" x-collapse x-transition class="px-6 pb-6 text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed border-t border-zinc-100 dark:border-zinc-700/50 pt-4">
                    <p>Sistem Kembangin membagi uang Anda menjadi 3 kualitas: Produktif (Investasi/Aset), Netral (Kewajiban/Bayar Hutang), dan Konsumtif (Uang Hangus). <br><br>
                    <strong>Pengeluaran Hangus</strong> adalah uang yang Anda keluarkan untuk gaya hidup yang nilainya tidak kembali (misal: Ngopi mahal, Baju fashion, Bioskop). Kami menampilkannya di halaman Analitik agar Anda sadar jika kebocoran gaya hidup Anda sudah melebihi batas aman.</p>
                </div>
            </div>

        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 mt-16 text-center animate-fade-in-up" style="animation-delay: 300ms;">
        <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-4">Masih punya pertanyaan lain?</p>
        <a href="https://wa.me/6285772797020" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full font-bold shadow-md hover:scale-105 transition-transform">
            <x-heroicon-s-chat-bubble-oval-left-ellipsis class="w-5 h-5" />
            Tanya Admin via WA
        </a>
    </div>

</div>
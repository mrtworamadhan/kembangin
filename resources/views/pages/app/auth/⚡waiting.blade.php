<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::auth')] class extends Component {
    public string $email = '';

    public function mount()
    {
        // Ambil email dari session (dikirim dari halaman register)
        $this->email = session('registered_email') ?? '';
    }
};
?>

<div class="animate-fade-in-up">
    <div class="bg-white dark:bg-zinc-800 p-8 rounded-3xl shadow-xl border border-zinc-100 dark:border-zinc-700 text-center relative overflow-hidden">
        
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-green-500 opacity-5 rounded-full blur-2xl"></div>

        <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
            <x-heroicon-o-clock class="w-10 h-10 animate-pulse" />
        </div>

        <h2 class="text-2xl font-extrabold text-zinc-800 dark:text-zinc-100 mb-2">Pendaftaran Berhasil! 🎉</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed mb-6">
            Demi menjaga kualitas & keamanan, akun Kembangin Anda <b>sedang dalam antrean persetujuan</b>. 
        </p>

        <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-6 text-left">
            <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1">Informasi Akun</p>
            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $email ?: 'Email Anda' }}</p>
            <p class="text-[10px] text-amber-600 font-bold mt-1 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span> Status: Pending Approval
            </p>
        </div>

        <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-3">Tidak sabar ingin langsung menggunakan Kembangin?</p>

        @php
            $noWaAdmin = '6285772797020'; 
            $pesanWa = urlencode("Halo Admin Kembangin! Saya sudah mendaftar dengan email: {$email}. Mohon bantuannya untuk aktivasi akun saya segera. Terima kasih!");
            $linkWa = "https://wa.me/{$noWaAdmin}?text={$pesanWa}";
        @endphp

        <a href="{{ $linkWa }}" target="_blank" class="w-full py-4 bg-[#25D366] hover:bg-[#1ebd5a] text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-green-600/30 flex justify-center items-center gap-2 mb-3">
            <x-heroicon-s-chat-bubble-oval-left-ellipsis class="w-5 h-5" />
            Chat Admin untuk Aktivasi Instan
        </a>

        <a href="{{ route('login') }}" class="block text-xs font-bold text-green-600 dark:text-green-400 hover:underline">
            Kembali ke Halaman Masuk
        </a>
    </div>
</div>
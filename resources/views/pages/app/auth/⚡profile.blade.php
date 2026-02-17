<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new #[Layout('layouts::auth')] class extends Component {
    
    public string $name = '';
    public string $email = '';
    
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount()
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);
        session()->flash('profile_message', 'Informasi profil berhasil diperbarui!');
    }

    public function updatePassword()
    {
        $validated = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('password_message', 'Password berhasil diperbarui!');
    }
};
?>

<div class="animate-fade-in space-y-6 pb-10">
    
    <div class="text-center">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-3xl font-extrabold mx-auto mb-3 shadow-sm border-4 border-white dark:border-zinc-800">
            {{ strtoupper(substr($name, 0, 1)) }}
        </div>
        <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $name }}</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Akun {{ ucfirst(Auth::user()->role) }}</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 p-6 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <x-heroicon-o-user class="w-5 h-5 text-green-500" /> Informasi Pribadi
        </h3>

        @if (session()->has('profile_message'))
            <div class="p-3 mb-4 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-5 h-5" /> {{ session('profile_message') }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" wire:model="name" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                @error('name') <span class="text-[10px] text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Email</label>
                <input type="email" wire:model="email" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                @error('email') <span class="text-[10px] text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="w-full py-3 bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900 font-bold rounded-xl text-sm hover:bg-zinc-900 transition">
                Simpan Perubahan
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-zinc-800 p-6 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-500" /> Keamanan Sandi
        </h3>

        @if (session()->has('password_message'))
            <div class="p-3 mb-4 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-xl text-xs font-bold flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-5 h-5" /> {{ session('password_message') }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Password Saat Ini</label>
                <input type="password" wire:model="current_password" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
                @error('current_password') <span class="text-[10px] text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Password Baru</label>
                <input type="password" wire:model="password" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
                @error('password') <span class="text-[10px] text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Konfirmasi Password Baru</label>
                <input type="password" wire:model="password_confirmation" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
            </div>
            <button type="submit" class="w-full py-3 bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900 font-bold rounded-xl text-sm hover:bg-zinc-900 transition">
                Ganti Password
            </button>
        </form>
    </div>

    <div class="pt-4">
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <button type="submit" @click.prevent="$root.submit();" class="w-full py-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 font-bold rounded-2xl text-sm border border-red-200 dark:border-red-800/50 hover:bg-red-100 transition flex items-center justify-center gap-2 shadow-sm">
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" /> Keluar dari Aplikasi
            </button>
        </form>
    </div>

</div>
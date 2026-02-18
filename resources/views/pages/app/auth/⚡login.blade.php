<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::auth')] class extends Component {
    
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function authenticate()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $this->remember)) {
            $user = Auth::user();

            if ($user->status === 'pending') {
                Auth::logout();
                
                session()->flash('registered_email', $user->email);
                
                return redirect()->route('waiting');
            }

            session()->regenerate();

            if ($user->role === 'owner') {
                return redirect()->route('app.home');
            }

            return redirect()->intended('/tenant'); 
        }

        $this->addError('email', 'Email atau password salah.');
    }
};
?>

<div class="animate-fade-in-up">
    <div class="text-center mb-5">
        <div class="w-64 h-24 flex items-center justify-center mx-auto mb-1 p-2 transform rotate-3">
            <img src="{{ asset('images/brand/logo.png') }}" alt="Kembangin" class="w-full h-full object-contain -rotate-3">
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">V.1.0-Beta</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 p-8 rounded-3xl shadow-xl border border-zinc-100 dark:border-zinc-700 relative overflow-hidden">
        
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-green-500 opacity-5 rounded-full blur-2xl"></div>

        <form wire:submit="authenticate" class="space-y-5 relative z-10">
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-heroicon-o-envelope class="w-5 h-5 text-zinc-400" />
                    </div>
                    <input type="email" wire:model="email" required autofocus autocomplete="username" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3.5 pl-11 pr-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="yourname@g***.com">
                </div>
                @error('email') <span class="text-xs text-red-500 mt-1.5 font-medium block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative" x-data="{ show: false }">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-zinc-400" />
                    </div>
                    <input :type="show ? 'text' : 'password'" wire:model="password" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3.5 pl-11 pr-12 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="••••••••">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 flex items-center text-zinc-400 hover:text-green-600 focus:outline-none">
                        <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                        <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" x-cloak />
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="remember" class="w-4 h-4 text-green-600 bg-zinc-100 border-zinc-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600">
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Ingat Saya</span>
                </label>
                <a href="#" class="text-xs font-bold text-green-600 dark:text-green-400 hover:underline">Lupa Password?</a>
            </div>

            <button type="submit" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-green-600/30 flex justify-center items-center gap-2 group mt-2">
                Masuk Sekarang
                <x-heroicon-o-arrow-right class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
        </form>
    </div>

    <p class="text-center text-xs font-medium text-zinc-500 mt-6">
        Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-green-600 hover:underline">Daftar sekarang</a>
    </p>

    <p class="text-center text-[10px] text-zinc-400 mt-4">
        &copy; {{ date('Y') }} Kembangin Family Wealth. All rights reserved.
    </p>
</div>
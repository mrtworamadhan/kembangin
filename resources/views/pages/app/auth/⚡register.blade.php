<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new #[Layout('layouts::auth')] class extends Component {
    
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone, 
            'password' => Hash::make($this->password),
            'role' => 'owner',
            'status' => 'pending',
        ]);

        session()->flash('registered_email', $user->email);

        return redirect()->route('waiting');
    }
};
?>

<div class="animate-fade-in-up">

    <div class="text-center mb-5">
        <div class="w-64 h-24 flex items-center justify-center mx-auto mb-1 p-2 transform rotate-3">
            <img src="{{ asset('images/brand/logo.png') }}" alt="Kembangin" class="w-full h-full object-contain -rotate-3">
        </div>
        <h1 class="text-3xl font-extrabold text-zinc-800 dark:text-zinc-100 font-serif tracking-tight">Daftar</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">Mulai langkah finansial yang lebih tenang.</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 p-8 rounded-3xl shadow-xl border border-zinc-100 dark:border-zinc-700 relative overflow-hidden">
        
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-orange-500 opacity-5 rounded-full blur-2xl"></div>

        <form wire:submit="register" class="space-y-4 relative z-10">
            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-heroicon-o-user class="w-5 h-5 text-zinc-400" />
                    </div>
                    <input type="text" wire:model="name" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3 pl-11 pr-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="Budi Santoso">
                </div>
                @error('name') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-heroicon-o-envelope class="w-5 h-5 text-zinc-400" />
                    </div>
                    <input type="email" wire:model="email" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3 pl-11 pr-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="budi@email.com">
                </div>
                @error('email') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nomor WhatsApp</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <x-heroicon-o-phone class="w-5 h-5 text-zinc-400" />
                    </div>
                    <input type="text" wire:model="phone" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3 pl-11 pr-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="081234567890">
                </div>
                @error('phone') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Password</label>
                    <input type="password" wire:model="password" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3 px-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Konfirmasi</label>
                    <input type="password" wire:model="password_confirmation" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl py-3 px-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm font-medium transition-all" placeholder="••••••••">
                </div>
            </div>
            @error('password') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror

            <button type="submit" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-green-600/30 flex justify-center items-center gap-2 group mt-4">
                Buat Akun Sekarang
                <x-heroicon-o-arrow-right class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
        </form>
    </div>

    <p class="text-center text-xs font-medium text-zinc-500 mt-6">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-green-600 hover:underline">Masuk di sini</a>
    </p>
</div>
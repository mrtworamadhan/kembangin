<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Account;
use App\Models\Transaction;
use \App\Models\Business;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Goal;
use App\Models\Purchase;
use App\Models\Order;
use App\Models\Product;


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

    // public function resetSemuaData()
    // {
    //     $userId = Auth::id();

    //     Transaction::where('user_id', $userId)->delete();
    //     Account::where('user_id', $userId)->update(['balance' => 0, 'opening_balance' => 0,]);
    //     Budget::where('user_id', $userId)->delete(); 
    //     Goal::where('user_id', $userId)->delete();
    //     Account::where('user_id', $userId)->delete();

    //     // --- 2. RESET PANEL BISNIS ---
    //     // Asumsi data bisnis juga terikat dengan user_id atau tenant_id (sesuaikan jika perlu)
    //     Purchase::where('user_id', $userId)->delete();
    //     Order::where('user_id', $userId)->delete();
    //     Product::where('user_id', $userId)->delete();

    //     // --- 3. KEMBALIKAN KE STATUS AWAL ---
    //     Auth::user()->update(['has_seen_tour' => false]);

    //     session()->flash('profile_message', 'BOOM! 💥 Semua data transaksi berhasil direset. Mari mulai lembaran baru!');
        
    //     return redirect()->route('app.home');
    // }

    public function resetSemuaData()
    {
        $userId = Auth::id();

        $businessIds = Business::where('user_id', $userId)->pluck('id');
        $orderIds = Order::whereIn('business_id', $businessIds)->pluck('id');
        $purchaseIds = Purchase::whereIn('business_id', $businessIds)->pluck('id');

        
        if ($businessIds->isNotEmpty()) {
            if ($orderIds->isNotEmpty()) {
                \App\Models\OrderItem::whereIn('order_id', $orderIds)->delete();
            }
            if ($purchaseIds->isNotEmpty()) {
                \App\Models\PurchaseItem::whereIn('purchase_id', $purchaseIds)->delete();
            }

            Purchase::whereIn('business_id', $businessIds)->delete();
            Order::whereIn('business_id', $businessIds)->delete();
            Product::whereIn('business_id', $businessIds)->delete();
            Customer::whereIn('business_id', $businessIds)->delete();
            Supplier::whereIn('business_id', $businessIds)->delete();
            Account::whereIn('business_id', $businessIds)->delete();

            Transaction::whereIn('business_id', $businessIds)->delete();

            Business::where('user_id', $userId)->delete();
        }

        Transaction::where('user_id', $userId)->delete();
        Budget::where('user_id', $userId)->delete(); 
        Goal::where('user_id', $userId)->delete();

        Account::where('user_id', $userId)
            ->update([
                'balance' => 0, 
                'opening_balance' => 0
            ]);

        Auth::user()->update(['has_seen_tour' => false]);

        session()->flash('profile_message', 'BOOM! 💥 Semua data Bisnis & Personal berhasil dibumihanguskan. Mari mulai lembaran baru!');
        
        return redirect()->route('app.home');
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

    <div x-data="{ showResetModal: false, confirmText: '' }" class="bg-red-50 dark:bg-red-900/10 p-6 rounded-3xl border border-red-200 dark:border-red-800/50 relative overflow-hidden">
        
        <div class="absolute -right-4 -top-4 opacity-10">
            <x-heroicon-s-exclamation-triangle class="w-32 h-32 text-red-600" />
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-red-800 dark:text-red-400 mb-1 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" /> Zona Berbahaya
                </h3>
                <p class="text-[11px] text-red-600 dark:text-red-300 font-medium leading-relaxed max-w-sm">
                    Tindakan ini akan menghapus seluruh data transaksi personal dan bisnis Anda secara permanen.
                </p>
            </div>
            
            <button @click="showResetModal = true" type="button" class="w-full sm:w-auto px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition shadow-sm whitespace-nowrap">
                Hapus & Mulai dari Nol
            </button>
        </div>

        <div 
            x-show="showResetModal" 
            style="display: none;"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0"
            x-cloak
        >
            <div 
                x-show="showResetModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm"
                @click="showResetModal = false; confirmText = ''"
            ></div>

            <div 
                x-show="showResetModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden border border-zinc-100 dark:border-zinc-800"
            >
                <div class="p-6">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center mb-4 border-4 border-red-50 dark:border-red-900/10">
                        <x-heroicon-s-trash class="w-6 h-6" />
                    </div>
                    
                    <h3 class="text-lg font-extrabold text-zinc-900 dark:text-white mb-2">Peringatan Penghapusan Data!</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4 leading-relaxed">
                        Anda akan melakukan reset pabrik pada akun ini. Tindakan ini <b>tidak dapat dibatalkan</b> dan akan menghapus data berikut:
                    </p>

                    <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4 mb-5 border border-zinc-100 dark:border-zinc-800 space-y-3">
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Panel Personal</p>
                            <ul class="text-xs text-zinc-600 dark:text-zinc-300 space-y-1.5 pl-4 list-disc marker:text-red-400">
                                <li>Seluruh Riwayat Transaksi (Pemasukan, Pengeluaran, Transfer)</li>
                                <li>Saldo Dompet/Rekening (Dikembalikan menjadi Rp 0)</li>
                                <li>Target Impian (Goals) dan Budget Rutin</li>
                            </ul>
                        </div>
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Panel Bisnis</p>
                            <ul class="text-xs text-zinc-600 dark:text-zinc-300 space-y-1.5 pl-4 list-disc marker:text-red-400">
                                <li>Riwayat Invoice dan Purchasing (Pembelian)</li>
                                <li>Data Produk dan Stok Inventory (Dikosongkan)</li>
                                <li>Seluruh Transaksi Operasional Bisnis</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            Ketik kata <span class="text-red-600 font-black tracking-widest bg-red-50 dark:bg-red-900/30 px-1 py-0.5 rounded">RESET</span> untuk mengonfirmasi:
                        </label>
                        <input 
                            type="text" 
                            x-model="confirmText" 
                            placeholder="Ketik RESET di sini..." 
                            class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500 text-sm placeholder:text-zinc-400 text-center font-bold tracking-widest uppercase"
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="showResetModal = false; confirmText = ''" 
                            class="flex-1 py-3 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl text-xs hover:bg-zinc-200 dark:hover:bg-zinc-700 transition"
                        >
                            Batal
                        </button>
                        <button 
                            @click="if(confirmText === 'RESET') { $wire.resetSemuaData(); } else { alert('Kata kunci tidak cocok.'); }"
                            :disabled="confirmText !== 'RESET'"
                            :class="confirmText === 'RESET' ? 'bg-red-600 hover:bg-red-700 text-white shadow-md' : 'bg-red-300 dark:bg-red-900/50 text-red-100 cursor-not-allowed'"
                            class="flex-1 py-3 font-bold rounded-xl text-xs transition"
                        >
                            Ya, Hapus Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
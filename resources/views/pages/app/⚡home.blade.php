<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Account;
use App\Models\Budget;
use Carbon\Carbon;

new #[Layout('layouts::pwa')] class extends Component
{
    public bool $showExpenseModal = false;
    public string $expenseAmount = '';
    public ?int $expenseAccountId = null;
    public ?int $expenseCategoryId = null;
    public string $expenseNotes = '';

    public function openExpenseModal()
    {
        $this->reset(['expenseAmount', 'expenseAccountId', 'expenseCategoryId', 'expenseNotes']);
        $this->showExpenseModal = true;
    }

    public function saveExpense()
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:1',
            'expenseAccountId' => 'required|exists:accounts,id',
            'expenseCategoryId' => 'required|exists:categories,id',
            'expenseNotes' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        Transaction::create([
            'business_id' => null,
            'user_id' => $user->id,
            'account_id' => $this->expenseAccountId,
            'category_id' => $this->expenseCategoryId,
            'amount' => $this->expenseAmount,
            'date' => now(),
            'description' => $this->expenseNotes ?: 'Pengeluaran Cepat',
        ]);

        $this->reset(['showExpenseModal', 'expenseAmount', 'expenseAccountId', 'expenseCategoryId', 'expenseNotes']);
        session()->flash('message', 'Pengeluaran cepat berhasil dicatat!');
    }

    public function with(): array
    {
        $user = Auth::user();
        $thisMonth = Carbon::now()->startOfMonth();
        $familyIds = $user->family_ids ?? [$user->id]; 

        // 1. DATA KAS FREE (UANG BEBAS)
        $personalWallets = Account::whereNull('business_id')->whereIn('user_id', $familyIds)->get()->map(function ($acc) {
            $income = Transaction::where('account_id', $acc->id)->whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
            $expense = Transaction::where('account_id', $acc->id)->whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
            $acc->current_balance = $acc->opening_balance + $income - $expense;
            return $acc;
        });

        $totalWalletBalance = $personalWallets->sum('current_balance');
        $totalLockedSavings = Goal::whereIn('user_id', $familyIds)->where('status', 'active')->sum('current_amount');
        $kasFree = $totalWalletBalance - $totalLockedSavings;
        if ($kasFree < 0) $kasFree = 0;

        // 2. DATA BUDGET / ANGGARAN RUTIN (SUMMARY)
        $budgets = Budget::whereIn('user_id', $familyIds)->get();
        $totalBudgetAmount = $budgets->sum('amount');
        
        $totalBudgetSpent = Transaction::whereIn('user_id', $familyIds)
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->where('date', '>=', $thisMonth)
            ->sum('amount');
            
        $budgetPercentage = $totalBudgetAmount > 0 ? min(100, round(($totalBudgetSpent / $totalBudgetAmount) * 100)) : 0;

        // 3. DATA ANALYTICS (PENGELUARAN PERSONAL & PROFIT BISNIS)
        $monthlyPersonalExpense = Transaction::whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q->where('type', 'expense'))
            ->sum('amount');

        $familyBusinesses = \App\Models\Business::whereHas('users', function($q) use ($familyIds) {
            $q->whereIn('users.id', $familyIds);
        })->pluck('id');

        $homeSales = Order::whereIn('business_id', $familyBusinesses)->where('order_date', '>=', $thisMonth)->sum('total_amount');
        $homePurchase = Purchase::whereIn('business_id', $familyBusinesses)->where('date', '>=', $thisMonth)->sum('total_amount');
        $homeOpEx = Transaction::whereIn('business_id', $familyBusinesses)->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', [
                'Bahan Baku / Pembelian Stok',
                'Penarikan Prive / Deviden',
            ]))->sum('amount');
        $businessProfit = $homeSales - $homePurchase - $homeOpEx;

        // 4. DATA TARGET IMPIAN TERDEKAT
        $activeGoal = Goal::whereIn('user_id', $familyIds)
            ->where('status', 'active')
            ->orderBy('deadline', 'asc')
            ->first();

        // 5. KATEGORI PENGELUARAN (Untuk Form Cepat)
        $expenseCategories = Category::where('group', 'personal')
            ->where('type', 'expense')
            ->whereIn('nature', ['need', 'want'])
            ->orderBy('name')
            ->get();

        return [
            'kasFree' => $kasFree,
            'totalWalletBalance' => $totalWalletBalance,
            'totalLockedSavings' => $totalLockedSavings,
            
            'totalBudgetAmount' => $totalBudgetAmount,
            'totalBudgetSpent' => $totalBudgetSpent,
            'budgetPercentage' => $budgetPercentage,
            
            'monthlyPersonalExpense' => $monthlyPersonalExpense,
            'businessProfit' => $businessProfit,
            
            'activeGoal' => $activeGoal,
            'userFirstname' => explode(' ', trim($user->name))[0],
            'personalAccounts' => $personalWallets,
            'expenseCategories' => $expenseCategories,
        ];
    }
};
?>

<div class="animate-fade-in space-y-5 pb-8">
                
    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 shadow-sm border border-green-200 dark:border-green-800 animate-fade-in-up">
            <x-heroicon-o-check-circle class="w-6 h-6 shrink-0" />
            <p class="text-sm font-bold">{{ session('message') }}</p>
        </div>
    @endif

    <div>
        <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
            Halo, {{ $userFirstname }}! <span class="text-xl">👋</span>
        </h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">
            {{ Carbon::now()->translatedFormat('l, d F Y') }}
        </p>
    </div>

    <div class="animate-fade-in-up">
        <a href="{{ route('app.assets') }}" wire:navigate class="block bg-gradient-to-br from-green-500 to-green-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden transition-transform hover:scale-[1.02] hover:shadow-green-500/30">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black opacity-10 rounded-t-full blur-xl"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-1 opacity-90">
                    <div class="flex items-center gap-1.5">
                        <x-heroicon-o-check-badge class="w-4 h-4" />
                        <p class="text-xs font-bold uppercase tracking-wider">Kas Free (Uang Jajan)</p>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 opacity-50" />
                </div>
                <h3 class="text-4xl font-extrabold tracking-tight mb-3">
                    Rp {{ number_format($kasFree, 0, ',', '.') }}
                </h3>
                
                <div class="flex items-center justify-between border-t border-white/20 pt-3 mt-2">
                    <div>
                        <p class="text-[9px] uppercase tracking-wider opacity-80 mb-0.5">Total Fisik</p>
                        <p class="text-sm font-bold">Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] uppercase tracking-wider opacity-80 mb-0.5 text-amber-200">Tabungan</p>
                        <p class="text-sm font-bold text-amber-100">Rp {{ number_format($totalLockedSavings, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </a>
        
        <button wire:click="openExpenseModal" class="w-full mt-3 py-3.5 bg-zinc-800 dark:bg-zinc-700 hover:bg-zinc-900 dark:hover:bg-zinc-600 text-white rounded-2xl font-bold text-sm transition shadow-md flex items-center justify-center gap-2">
            <x-heroicon-o-bolt class="w-5 h-5 text-amber-400" />
            Catat Pengeluaran
        </button>
    </div>

    <a href="{{ route('app.assets') }}" wire:navigate class="block bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 transition-transform hover:scale-[1.02] hover:border-blue-500/50 group">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg group-hover:bg-blue-100 transition-colors">
                    <x-heroicon-o-chart-bar-square class="w-4 h-4" />
                </div>
                <h3 class="text-xs font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">Anggaran Rutin</h3>
            </div>
            <x-heroicon-o-chevron-right class="w-4 h-4 text-zinc-400 group-hover:text-blue-500" />
        </div>
        
        <div class="flex justify-between items-end mb-1.5">
            <p class="text-lg font-extrabold text-zinc-800 dark:text-zinc-100">
                Rp {{ number_format($totalBudgetSpent, 0, ',', '.') }}
            </p>
            <p class="text-[10px] font-bold text-zinc-500">
                Limit: Rp {{ number_format($totalBudgetAmount, 0, ',', '.') }}
            </p>
        </div>

        @php
            if($budgetPercentage < 75) $barColor = 'bg-green-500';
            elseif($budgetPercentage <= 100) $barColor = 'bg-amber-500';
            else $barColor = 'bg-red-500';
        @endphp
        
        <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2 mt-2 overflow-hidden">
            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-1000" style="width: {{ $budgetPercentage }}%"></div>
        </div>
    </a>

    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('app.analytics') }}" wire:navigate class="block bg-white dark:bg-zinc-800 p-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 relative overflow-hidden transition-transform hover:scale-[1.02] hover:border-rose-500/50 group">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-rose-50 dark:bg-rose-900/20 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-center justify-between mb-2 relative z-10">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-lg">
                        <x-heroicon-o-fire class="w-4 h-4" />
                    </div>
                    <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Pengeluaran</p>
                </div>
            </div>
            <p class="text-lg font-extrabold text-zinc-800 dark:text-zinc-100 relative z-10">
                Rp {{ number_format($monthlyPersonalExpense, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1 relative z-10 flex items-center justify-between">
                Bulan Ini <x-heroicon-o-arrow-right class="w-3 h-3 group-hover:text-rose-500 transition-colors" />
            </p>
        </a>

        <a href="{{ route('app.analytics') }}" wire:navigate class="block bg-white dark:bg-zinc-800 p-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 relative overflow-hidden transition-transform hover:scale-[1.02] hover:border-emerald-500/50 group">
            <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex items-center justify-between mb-2 relative z-10">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg {{ $businessProfit >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                        <x-heroicon-o-presentation-chart-line class="w-4 h-4" />
                    </div>
                    <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Est. Profit</p>
                </div>
            </div>
            <p class="text-lg font-extrabold {{ $businessProfit >= 0 ? 'text-zinc-800 dark:text-zinc-100' : 'text-red-600 dark:text-red-400' }} relative z-10">
                Rp {{ number_format($businessProfit, 0, ',', '.') }}
            </p>
            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1 relative z-10 flex items-center justify-between">
                Gabungan Toko <x-heroicon-o-arrow-right class="w-3 h-3 group-hover:text-emerald-500 transition-colors" />
            </p>
        </a>
    </div>

    <div>
        <div class="flex justify-between items-end mb-3">
            <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200">Fokus Impian Terdekat</h3>
        </div>

        @if($activeGoal)
            @php
                $percentage = $activeGoal->target_amount > 0 
                    ? min(100, round(($activeGoal->current_amount / $activeGoal->target_amount) * 100)) 
                    : 0;
            @endphp
            <a href="{{ route('app.assets') }}" wire:navigate class="block bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 group transition-transform hover:scale-[1.02] hover:border-amber-500/50">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 flex items-center justify-center bg-amber-50 dark:bg-amber-900/30 text-amber-500 rounded-full group-hover:bg-amber-100 transition-colors">
                            <x-heroicon-o-star class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="font-bold text-zinc-800 dark:text-zinc-100 text-sm">{{ $activeGoal->name }}</p>
                            @if($activeGoal->deadline)
                                <p class="text-[9px] font-bold text-amber-500 flex items-center gap-1 mt-0.5">
                                    <x-heroicon-o-clock class="w-3 h-3" /> Target: {{ \Carbon\Carbon::parse($activeGoal->deadline)->translatedFormat('d M Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-sm font-extrabold text-green-600 dark:text-green-400">{{ $percentage }}%</span>
                        <x-heroicon-o-chevron-right class="w-4 h-4 text-zinc-400 group-hover:text-amber-500 mt-1" />
                    </div>
                </div>
                
                <div class="flex justify-between text-[10px] font-bold text-zinc-500 mb-1.5">
                    <span>Terkumpul: Rp {{ number_format($activeGoal->current_amount, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($activeGoal->target_amount, 0, ',', '.') }}</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2 mt-1 overflow-hidden">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                </div>
            </a>
        @else
            <a href="{{ route('app.assets') }}" wire:navigate class="block border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-3xl p-6 flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                <x-heroicon-o-plus-circle class="w-8 h-8 mb-2 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm font-bold">Belum ada target tabungan</p>
                <p class="text-xs text-zinc-400 mt-1">Klik untuk buat impian barumu!</p>
            </a>
        @endif
    </div>

    @if($showExpenseModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <x-heroicon-o-bolt class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">
                    Pengeluaran Cepat
                </h3>
                <p class="text-[10px] text-center text-zinc-500 dark:text-zinc-400 mb-5">
                    Memotong langsung dari <strong class="text-green-600">Kas Free</strong> mu.
                </p>

                <form wire:submit="saveExpense" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">Dompet Sumber</label>
                        <select wire:model="expenseAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm font-semibold">
                            <option value="">-- Pilih --</option>
                            @foreach($personalAccounts as $acc) 
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option> 
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">Kategori Pengeluaran</label>
                        <select wire:model="expenseCategoryId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm font-semibold">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($expenseCategories as $cat) 
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option> 
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                            <input type="number" wire:model="expenseAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-4 pl-12 focus:ring-2 focus:ring-green-500 font-extrabold text-xl shadow-inner" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">Catatan</label>
                        <input type="text" wire:model="expenseNotes" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm" placeholder="Opsional...">
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showExpenseModal', false)" class="flex-1 py-3 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl text-white bg-zinc-800 hover:bg-zinc-900 dark:bg-zinc-700 dark:hover:bg-zinc-600 font-bold text-sm transition shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
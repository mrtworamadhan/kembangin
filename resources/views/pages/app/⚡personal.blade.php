<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Goal;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Account;
use App\Models\Category;
use App\Models\Business;
use Carbon\Carbon;

new class extends Component
{
    public string $currentTab = 'home';
    public ?int $selectedBusinessId = null;

    public string $transferType = 'withdraw';
    public ?int $transferBusinessId = null;
    public ?int $fromAccountId = null;
    public ?int $toAccountId = null;
    public $transferAmount = '';
    public string $transferNotes = '';

    public bool $showWalletModal = false;
    public string $newWalletName = '';
    public string $newWalletBalance = '';

    public string $ledgerBusinessId = '';

    public ?int $viewingWalletId = null;

    public bool $showGoalModal = false;
    public string $goalName = '';
    public string $goalType = 'monthly';
    public string $goalTargetAmount = '';
    public string $goalDeadline = '';

    public bool $showAddFundModal = false;
    public ?int $fundGoalId = null;
    public string $fundAmount = '';

    public bool $showExpenseModal = false;
    public string $expenseAmount = '';
    public ?int $expenseAccountId = null;
    public string $expenseNotes = '';
    public ?int $expenseGoalId = null;

    public function mount()
    {
        $user = Auth::user();
        
        $firstBusiness = $user->businesses()->first();
        if ($firstBusiness) {
            $this->selectedBusinessId = $firstBusiness->id;
            $this->transferBusinessId = $firstBusiness->id;
        }
    }

    public function setTab(string $tab)
    {
        $this->currentTab = $tab;
        
    }

    public function backToBusiness()
    {
        return redirect('/tenant'); 
    }

    public function setTransferType($type)
    {
        $this->transferType = $type;
        $this->fromAccountId = null;
        $this->toAccountId = null;
    }

    public function viewWallet($id)
    {
        $this->viewingWalletId = $id;
    }

    public function closeWallet()
    {
        $this->viewingWalletId = null;
    }

    public function saveWallet()
    {
        $this->validate([
            'newWalletName' => 'required|string|max:255',
            'newWalletBalance' => 'nullable|numeric|min:0',
        ]);

        Account::create([
            'user_id' => Auth::id(),
            'business_id' => null,
            'name' => $this->newWalletName,
            'opening_balance' => $this->newWalletBalance ?: 0,
        ]);

        $this->reset(['newWalletName', 'newWalletBalance', 'showWalletModal']);
        session()->flash('message', 'Dompet Pribadi baru berhasil dibuat!');
    }

    public function submitTransfer()
    {
        $this->validate([
            'transferBusinessId' => 'required',
            'fromAccountId' => 'required',
            'toAccountId' => 'required',
            'transferAmount' => 'required|numeric|min:1',
        ]);

        $business = Business::find($this->transferBusinessId);
        $user = Auth::user();

        if ($this->transferType === 'withdraw') {
            $bizCategory = Category::firstOrCreate(
                ['business_id' => $business->id, 'name' => 'Penarikan Prive / Deviden', 'type' => 'expense']
            );
            Transaction::create([
                'business_id' => $business->id,
                'account_id' => $this->fromAccountId,
                'category_id' => $bizCategory->id,
                'amount' => $this->transferAmount,
                'date' => now(),
                'description' => $this->transferNotes ?: 'Penarikan ke rekening pribadi',
            ]);

            $personalCategory = Category::firstOrCreate(
                ['business_id' => null, 'user_id' => $user->id, 'name' => 'Hasil Bisnis: ' . $business->name, 'type' => 'income']
            );
            Transaction::create([
                'business_id' => null,
                'user_id' => $user->id,
                'account_id' => $this->toAccountId, 
                'category_id' => $personalCategory->id,
                'amount' => $this->transferAmount,
                'date' => now(),
                'description' => $this->transferNotes ?: 'Pemasukan dari ' . $business->name,
            ]);

        } else {
            $personalCategory = Category::firstOrCreate(
                ['business_id' => null, 'user_id' => $user->id, 'name' => 'Suntik Modal: ' . $business->name, 'type' => 'expense']
            );
            Transaction::create([
                'business_id' => null,
                'user_id' => $user->id,
                'account_id' => $this->fromAccountId, 
                'category_id' => $personalCategory->id,
                'amount' => $this->transferAmount,
                'date' => now(),
                'description' => $this->transferNotes ?: 'Suntik modal ke ' . $business->name,
            ]);

            $bizCategory = Category::firstOrCreate(
                ['business_id' => $business->id, 'name' => 'Suntikan Modal Tambahan', 'type' => 'income']
            );
            Transaction::create([
                'business_id' => $business->id,
                'account_id' => $this->toAccountId,
                'category_id' => $bizCategory->id,
                'amount' => $this->transferAmount,
                'date' => now(),
                'description' => $this->transferNotes ?: 'Suntikan modal dari pribadi',
            ]);
        }

        session()->flash('message', 'Transfer lintas domain berhasil dicatat!');
        $this->reset(['transferAmount', 'transferNotes', 'fromAccountId', 'toAccountId']);
    }

    public function saveGoal()
    {
        $this->validate([
            'goalName' => 'required|string|max:255',
            'goalType' => 'required|in:monthly,dream',
            'goalTargetAmount' => 'required|numeric|min:1',
            'goalDeadline' => 'nullable|date',
        ]);

        Goal::create([
            'user_id' => Auth::id(),
            'name' => $this->goalName,
            'type' => $this->goalType,
            'target_amount' => $this->goalTargetAmount,
            'current_amount' => 0,
            'deadline' => $this->goalDeadline ?: null,
            'status' => 'active',
        ]);

        $this->reset(['goalName', 'goalType', 'goalTargetAmount', 'goalDeadline', 'showGoalModal']);
        session()->flash('message', 'Target berhasil ditambahkan!');
    }

    public function openAddFund($id)
    {
        $this->fundGoalId = $id;
        $this->fundAmount = '';
        $this->showAddFundModal = true;
    }

    public function openExpenseModal($goalId = null)
    {
        $this->reset(['expenseAmount', 'expenseAccountId', 'expenseNotes']);
        $this->expenseGoalId = $goalId;
        
        if ($goalId) {
            $goal = Goal::find($goalId);
            $this->expenseNotes = 'Realisasi: ' . $goal->name;
            $this->expenseAmount = $goal->current_amount > 0 ? $goal->current_amount : ''; 
        }
        
        $this->showExpenseModal = true;
    }

    public function saveExpense()
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:1',
            'expenseAccountId' => 'required|exists:accounts,id',
            'expenseNotes' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $categoryName = $this->expenseGoalId ? 'Realisasi Target' : 'Pengeluaran Rumah Tangga';
        $category = Category::firstOrCreate([
            'business_id' => null,
            'user_id' => $user->id,
            'name' => $categoryName,
            'type' => 'expense'
        ]);

        Transaction::create([
            'business_id' => null,
            'user_id' => $user->id,
            'account_id' => $this->expenseAccountId,
            'category_id' => $category->id,
            'amount' => $this->expenseAmount,
            'date' => now(),
            'description' => $this->expenseNotes,
        ]);

        if ($this->expenseGoalId) {
            $goal = Goal::find($this->expenseGoalId);
            if ($goal) {
                $goal->current_amount -= $this->expenseAmount;
                if ($goal->current_amount < 0) $goal->current_amount = 0; 
                $goal->save();
            }
        }

        $this->reset(['showExpenseModal', 'expenseAmount', 'expenseAccountId', 'expenseNotes', 'expenseGoalId']);
        session()->flash('message', 'Pengeluaran berhasil dicatat!');
    }

    // --- FUNGSI SIMPAN TOP-UP TABUNGAN ---
    public function saveFund()
    {
        $this->validate([
            'fundAmount' => 'required|numeric|min:1',
        ]);

        $goal = Goal::where('user_id', Auth::id())->find($this->fundGoalId);
        if ($goal) {
            $goal->current_amount += $this->fundAmount;
            
            if ($goal->current_amount >= $goal->target_amount && $goal->type === 'dream') {
                $goal->status = 'achieved'; 
            }
            $goal->save();
            
            session()->flash('message', 'Tabungan berhasil ditambahkan ke ' . $goal->name . '!');
        }

        $this->reset(['showAddFundModal', 'fundGoalId', 'fundAmount']);
    }

    public function with(): array
    {
        $user = Auth::user();
        $thisMonth = Carbon::now()->startOfMonth();

        $personalAccounts = Account::whereNull('business_id')
            ->where('user_id', $user->id)->get();
        $businessAccounts = Account::where('business_id', $this->transferBusinessId)
            ->get();

        $ledgerQuery = Transaction::whereNull('business_id')
            ->where('user_id', $user->id)
            ->whereHas('category', function($q) {
                $q->where('name', 'like', 'Hasil Bisnis:%')
                  ->orWhere('name', 'like', 'Suntik Modal:%');
            });

        if ($this->ledgerBusinessId !== '') {
            $filterBizName = Business::find($this->ledgerBusinessId)->name ?? '';
            $ledgerQuery->whereHas('category', function($q) use ($filterBizName) {
                $q->where('name', 'like', '%' . $filterBizName . '%');
            });
        }

        $ledgerMutations = $ledgerQuery->orderBy('date', 'desc')->orderBy('id', 'desc')->limit(10)->get();

        $personalIncome = Transaction::whereNull('business_id')
            ->where('user_id', $user->id)
            ->whereHas('category', fn($q) => $q
            ->where('type', 'income'))->sum('amount');

        $personalExpense = Transaction::whereNull('business_id')
            ->where('user_id', $user->id)
            ->whereHas('category', fn($q) => $q
            ->where('type', 'expense'))->sum('amount');

        $personalSaldoAwal = Account::whereNull('business_id')
            ->where('user_id', $user->id)
            ->sum('opening_balance'); 

        $personalBalance = $personalSaldoAwal + $personalIncome - $personalExpense;

        $businessIds = $user->businesses()->pluck('businesses.id');
        $homeSales = Order::whereIn('business_id', $businessIds)
            ->where('order_date', '>=', $thisMonth)
            ->sum('total_amount');
        $homePurchase = Purchase::whereIn('business_id', $businessIds)
            ->where('date', '>=', $thisMonth)
            ->sum('total_amount');
        $homeOpEx = Transaction::whereIn('business_id', $businessIds)
            ->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q
                ->where('type', 'expense')
                ->where('name', '!=', 'Pembelian Stok'))->sum('amount');
        $businessProfit = $homeSales - $homePurchase - $homeOpEx;

        // --- DATA TAB WALLET (DOMPET PRIBADI) ---
        $personalWallets = Account::whereNull('business_id')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($acc) {
                // Hitung Pemasukan ke dompet ini
                $income = Transaction::where('account_id', $acc->id)
                    ->whereHas('category', fn($q) => $q->where('type', 'income'))
                    ->sum('amount');
                
                // Hitung Pengeluaran dari dompet ini
                $expense = Transaction::where('account_id', $acc->id)
                    ->whereHas('category', fn($q) => $q->where('type', 'expense'))
                    ->sum('amount');
                
                // Set saldo saat ini
                $acc->current_balance = $acc->opening_balance + $income - $expense;
                return $acc;
            });

        // Hitung total gabungan semua dompet
        $totalWalletBalance = $personalWallets->sum('current_balance');

        $monthlyPersonalExpense = Transaction::whereNull('business_id')
            ->where('user_id', $user->id)->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q
            ->where('type', 'expense'))->sum('amount');
        $activeGoal = Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('deadline', 'asc')
            ->first();


        $selectedBizData = [
            'sales' => 0, 'profit' => 0, 'piutang' => 0, 'hutang' => 0, 'kas' => 0, 'withdraw' => 0
        ];

        if ($this->selectedBusinessId) {
            $sales = Order::where('business_id', $this->selectedBusinessId)
                ->where('order_date', '>=', $thisMonth)->sum('total_amount');
            
            $purchases = Purchase::where('business_id', $this->selectedBusinessId)
                ->where('date', '>=', $thisMonth)->sum('total_amount');

            $opEx = Transaction::where('business_id', $this->selectedBusinessId)
                ->where('date', '>=', $thisMonth)
                ->whereHas('category', fn($q) => $q->where('type', 'expense')->where('name', '!=', 'Pembelian Stok'))
                ->sum('amount');

            $profit = $sales - $purchases - $opEx;

            $piutang = Order::where('business_id', $this->selectedBusinessId)
                ->where('payment_status', 'unpaid')->sum('total_amount');

            $hutang = Purchase::where('business_id', $this->selectedBusinessId)
                ->where('payment_status', 'unpaid')->sum('total_amount');

            
            $bizTotalIncome = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
                
            $bizTotalExpense = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
                
            $saldoAwal = Account::where('business_id', $this->selectedBusinessId)
                ->sum('opening_balance'); 
            $kasBisnis = $saldoAwal + $bizTotalIncome - $bizTotalExpense;

            $totalWithdraw = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereHas('category', function($q) {
                    $q->where('type', 'expense')
                      ->where(function($query) {
                          $query->where('name', 'like', '%Prive%')
                                ->orWhere('name', 'like', '%Deviden%')
                                ->orWhere('name', 'like', '%Tarik%');
                      });
                })->sum('amount');

            $selectedBizData = [
                'sales' => $sales,
                'profit' => $profit,
                'piutang' => $piutang,
                'hutang' => $hutang,
                'kas' => $kasBisnis,
                'withdraw' => $totalWithdraw,
            ];
        }

        $walletMutations = collect();
        $viewingWallet = null;

        if ($this->viewingWalletId) {
            $viewingWallet = Account::find($this->viewingWalletId);
            $walletMutations = Transaction::where('account_id', $this->viewingWalletId)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();
        }

        $monthlyNeeds = Goal::where('user_id', $user->id)
            ->where('type', 'monthly')
            ->orderBy('id', 'asc')
            ->get();
            
        // Hitung totalan Kebutuhan Pokok
        $totalMonthlyTarget = $monthlyNeeds->sum('target_amount');
        $totalMonthlyCurrent = $monthlyNeeds->sum('current_amount');

        // 2. Impian / Tabungan Bebas (Hanya yang masih active)
        $dreamGoals = Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('type', 'dream')
            ->orderBy('deadline', 'asc')
            ->orderBy('id', 'desc')
            ->get();
            
        // Hitung totalan Impian
        $totalDreamTarget = $dreamGoals->sum('target_amount');
        $totalDreamCurrent = $dreamGoals->sum('current_amount');
            
        // 3. Yang Sudah Tercapai
        $achievedGoals = Goal::where('user_id', $user->id)
            ->where('status', 'achieved')
            ->orderBy('updated_at', 'desc')
            ->get();

        return [
            'personalBalance' => $personalBalance,
            'businessProfit' => $businessProfit,
            'monthlyPersonalExpense' => $monthlyPersonalExpense,
            'activeGoal' => $activeGoal,
            'userFirstname' => explode(' ', trim($user->name))[0],
            'userBusinesses' => $user->businesses,
            'bizData' => $selectedBizData,
            'personalAccounts' => $personalAccounts,
            'businessAccounts' => $businessAccounts,
            'ledgerMutations' => $ledgerMutations,
            'personalWallets' => $personalWallets,
            'totalWalletBalance' => $totalWalletBalance,
            'viewingWallet' => $viewingWallet,
            'walletMutations' => $walletMutations,
            'monthlyNeeds' => $monthlyNeeds, 
            'totalMonthlyTarget' => $totalMonthlyTarget,
            'totalMonthlyCurrent' => $totalMonthlyCurrent,
            'dreamGoals' => $dreamGoals,     
            'totalDreamTarget' => $totalDreamTarget,
            'totalDreamCurrent' => $totalDreamCurrent,
            'achievedGoals' => $achievedGoals,
        ];
    }
};
?>

<div 
    x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
    x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
    :class="{ 'dark': darkMode }"
>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 font-sans pb-24 transition-colors duration-300">
        
        <div class="bg-green-600 dark:bg-green-800 text-white px-4 py-4 shadow-md sticky top-0 z-50 flex justify-between items-center transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button wire:click="backToBusiness" class="p-2 bg-green-700 dark:bg-green-900 rounded-full hover:bg-green-800 dark:hover:bg-green-700 transition">
                    <x-heroicon-o-arrow-left class="w-5 h-5" />
                </button>
                <h1 class="text-xl font-bold tracking-wide">Keuangan Keluarga</h1>
            </div>
            
            <div class="flex items-center gap-3">
                <button @click="darkMode = !darkMode" class="p-2 rounded-full hover:bg-green-700 dark:hover:bg-green-700 transition">
                    <x-heroicon-o-moon x-show="!darkMode" class="w-5 h-5" />
                    <x-heroicon-o-sun x-show="darkMode" class="w-5 h-5" style="display: none;" />
                </button>

                <div class="w-8 h-8 rounded-full bg-white dark:bg-zinc-800 text-green-600 dark:text-green-400 flex items-center justify-center font-bold shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </div>

        <div class="p-4 max-w-md mx-auto">
            
            @if($currentTab === 'home')
            <div class="animate-fade-in space-y-5">
                
                <div>
                    <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
                        Halo, {{ $userFirstname }}! 👋
                    </h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ Carbon::now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>

                <div>
                    <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-20 h-20 bg-black opacity-10 rounded-full blur-lg"></div>
                        
                        <div class="relative z-10">
                            <p class="text-green-100 text-sm font-medium mb-1">Total Uang Pribadi</p>
                            <h3 class="text-3xl font-extrabold tracking-tight">
                                Rp {{ number_format($personalBalance, 0, ',', '.') }}
                            </h3>
                        </div>
                    </div>
                    <button wire:click="openExpenseModal" class="w-full mt-3 py-3.5 bg-zinc-800 dark:bg-zinc-700 hover:bg-zinc-900 dark:hover:bg-zinc-600 text-white rounded-2xl font-bold text-sm transition shadow-sm flex items-center justify-center gap-2">
                        <x-heroicon-o-receipt-percent class="w-5 h-5" />
                        Catat Pengeluaran Rumah
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 rounded-lg {{ $businessProfit >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' }}">
                                <x-heroicon-o-banknotes class="w-4 h-4" />
                            </div>
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Est. Profit</p>
                        </div>
                        
                        <p class="text-lg font-bold {{ $businessProfit >= 0 ? 'text-zinc-800 dark:text-zinc-100' : 'text-red-600 dark:text-red-400' }}">
                            Rp {{ number_format($businessProfit, 0, ',', '.') }}
                        </p>
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1 leading-tight">
                            Bulan Ini <br>
                            <span class="text-[9px] italic">(Termasuk Piutang & Hutang)</span>
                        </p>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-1.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                                <x-heroicon-o-fire class="w-4 h-4" />
                            </div>
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Pengeluaran</p>
                        </div>
                        <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                            Rp {{ number_format($monthlyPersonalExpense, 0, ',', '.') }}
                        </p>
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Rumah Tangga</p>
                    </div>

                </div>

                <div>
                    <div class="flex justify-between items-end mb-3">
                        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200">Target Terdekat</h3>
                        <button wire:click="setTab('target')" class="text-xs text-green-600 dark:text-green-400 font-medium hover:underline">Lihat Semua</button>
                    </div>

                    @if($activeGoal)
                        @php
                            $percentage = $activeGoal->target_amount > 0 
                                ? min(100, round(($activeGoal->current_amount / $activeGoal->target_amount) * 100)) 
                                : 0;
                        @endphp
                        <div class="bg-white dark:bg-zinc-800 p-5 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full">
                                        <x-heroicon-o-star class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-zinc-800 dark:text-zinc-100 text-sm">{{ $activeGoal->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Rp {{ number_format($activeGoal->current_amount, 0, ',', '.') }} / Rp {{ number_format($activeGoal->target_amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $percentage }}%</span>
                            </div>
                            
                            <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 mt-3 overflow-hidden">
                                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @else
                        <div wire:click="setTab('target')" class="cursor-pointer border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <x-heroicon-o-plus-circle class="w-8 h-8 mb-2 text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm font-medium">Buat Target Tabungan Baru</p>
                        </div>
                    @endif
                </div>

            </div>
            
            @elseif($currentTab === 'business')
                <div class="animate-fade-in space-y-5">
                    
                    <div class="flex flex-col gap-2">
                        <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Raport Bisnis</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Pilih toko untuk melihat performa & posisi kas.</p>
                        
                        <div class="relative mt-2">
                            <select wire:model.live="selectedBusinessId" class="w-full appearance-none bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 py-3 px-4 pr-8 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-semibold">
                                @foreach($userBusinesses as $biz)
                                    <option value="{{ $biz->id }}">{{ $biz->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                                <x-heroicon-o-chevron-down class="w-5 h-5" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 relative overflow-hidden">
                        <div class="flex justify-between items-start relative z-10">
                            <div>
                                <p class="text-zinc-500 dark:text-zinc-400 text-xs font-bold uppercase tracking-wider mb-1">Est. Laba Bersih</p>
                                <h3 class="text-3xl font-extrabold {{ $bizData['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    Rp {{ number_format($bizData['profit'], 0, ',', '.') }}
                                </h3>
                                <p class="text-xs text-zinc-400 mt-1">Bulan Ini (Di atas kertas)</p>
                            </div>
                            <div class="p-3 rounded-2xl {{ $bizData['profit'] >= 0 ? 'bg-green-50 dark:bg-green-900/30 text-green-600' : 'bg-red-50 dark:bg-red-900/30 text-red-600' }}">
                                <x-heroicon-o-presentation-chart-line class="w-7 h-7" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        
                        <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 dark:from-zinc-700 dark:to-zinc-800 p-4 rounded-2xl shadow-md text-white border border-zinc-700">
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider font-semibold mb-2">Kas Bisnis Saat Ini</p>
                            <p class="text-xl font-bold text-green-400">
                                Rp {{ number_format($bizData['kas'], 0, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-zinc-500 mt-1">Uang riil di bank/laci</p>
                        </div>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                            <div class="flex items-center gap-1.5 mb-2">
                                <x-heroicon-o-arrow-right-circle class="w-4 h-4 text-purple-500" />
                                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Ditarik</p>
                            </div>
                            <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                                Rp {{ number_format($bizData['withdraw'], 0, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-zinc-400 mt-1">Ke rekening pribadi</p>
                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 border-l-4 border-l-amber-500">
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Piutang</p>
                            <p class="text-lg font-bold text-amber-600 dark:text-amber-500">
                                Rp {{ number_format($bizData['piutang'], 0, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-zinc-400 mt-1 leading-tight">Uang nyangkut <br>di customer</p>
                        </div>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 border-l-4 border-l-red-500">
                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Hutang</p>
                            <p class="text-lg font-bold text-red-600 dark:text-red-500">
                                Rp {{ number_format($bizData['hutang'], 0, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-zinc-400 mt-1 leading-tight">PO ke supplier <br>belum dibayar</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded-xl">
                                <x-heroicon-o-shopping-bag class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Penjualan</p>
                                <p class="text-[10px] text-zinc-400">Omzet Bulan Ini</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                            Rp {{ number_format($bizData['sales'], 0, ',', '.') }}
                        </p>
                    </div>

                </div>

            @elseif($currentTab === 'transfer')
                <div class="animate-fade-in space-y-6">
                    
                    <div class="flex flex-col gap-1">
                        <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Transfer & Mutasi</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Jembatan antara kas toko dan dompet pribadi.</p>
                    </div>

                    @if (session()->has('message'))
                        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 shadow-sm border border-green-200 dark:border-green-800">
                            <x-heroicon-o-check-circle class="w-6 h-6" />
                            <p class="text-sm font-bold">{{ session('message') }}</p>
                        </div>
                    @endif

                    <div class="bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl flex relative w-full border border-zinc-200 dark:border-zinc-700 shadow-sm">
                        <button wire:click="setTransferType('withdraw')" class="flex-1 py-2.5 text-sm font-bold rounded-xl transition-all {{ $transferType === 'withdraw' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                            Tarik Deviden
                        </button>
                        <button wire:click="setTransferType('inject')" class="flex-1 py-2.5 text-sm font-bold rounded-xl transition-all {{ $transferType === 'inject' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                            Suntik Modal
                        </button>
                    </div>

                    <form wire:submit="submitTransfer" class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 space-y-4 relative">
                        
                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Bisnis Terkait</label>
                            <select wire:model.live="transferBusinessId" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 font-semibold appearance-none">
                                @foreach($userBusinesses as $biz) <option value="{{ $biz->id }}">{{ $biz->name }}</option> @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 relative">
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="bg-green-100 dark:bg-green-900/80 p-1.5 rounded-full text-green-600 dark:text-green-400 z-10 border-2 border-white dark:border-zinc-800">
                                    <x-heroicon-o-arrow-right class="w-4 h-4" />
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Dari Dompet</label>
                                    @if($transferType === 'inject') <button type="button" wire:click="$set('showWalletModal', true)" class="text-[10px] text-green-600 dark:text-green-400 font-bold hover:underline">+ Buat</button>
                                    @endif
                                </div>
                                <select wire:model="fromAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                                    <option value="">-- Pilih --</option>
                                    @if($transferType === 'withdraw')
                                        @foreach($businessAccounts as $acc) <option value="{{ $acc->id }}">{{ $acc->name }}</option> @endforeach
                                    @else
                                        @foreach($personalAccounts as $acc) <option value="{{ $acc->id }}">{{ $acc->name }}</option> @endforeach
                                    @endif
                                </select>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    @if($transferType === 'withdraw') <button type="button" wire:click="$set('showWalletModal', true)" class="text-[10px] text-green-600 dark:text-green-400 font-bold hover:underline">+ Buat</button>
                                    @else <span></span> @endif
                                    <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-right">Ke Dompet</label>
                                </div>
                                <select wire:model="toAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                                    <option value="">-- Pilih --</option>
                                    @if($transferType === 'withdraw')
                                        @foreach($personalAccounts as $acc) <option value="{{ $acc->id }}">{{ $acc->name }}</option> @endforeach
                                    @else
                                        @foreach($businessAccounts as $acc) <option value="{{ $acc->id }}">{{ $acc->name }}</option> @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                                <input type="number" wire:model="transferAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 pl-10 focus:ring-2 focus:ring-green-500 font-bold text-lg" placeholder="0">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Catatan</label>
                            <input type="text" wire:model="transferNotes" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm" placeholder="Opsional...">
                        </div>

                        <button type="submit" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold transition-colors flex justify-center items-center gap-2 mt-2 shadow-sm">
                            <x-heroicon-o-paper-airplane class="w-5 h-5" />
                            Eksekusi
                        </button>
                    </form>

                    <div class="border-t border-zinc-200 dark:border-zinc-700/50 my-6"></div>

                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">Riwayat Mutasi Lintas Domain</h3>
                            
                            <select wire:model.live="ledgerBusinessId" class="text-xs bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg px-2 py-1 shadow-sm focus:ring-1 focus:ring-green-500 outline-none">
                                <option value="">Semua Bisnis</option>
                                @foreach($userBusinesses as $biz) <option value="{{ $biz->id }}">{{ $biz->name }}</option> @endforeach
                            </select>
                        </div>

                        <div class="space-y-3">
                            @forelse($ledgerMutations as $mutasi)
                                @php
                                    $isWithdraw = $mutasi->category->type === 'income'; 
                                @endphp
                                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl {{ $isWithdraw ? 'bg-green-100 dark:bg-green-900/40 text-green-600' : 'bg-red-100 dark:bg-red-900/40 text-red-600' }}">
                                            @if($isWithdraw)
                                                <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                            @else
                                                <x-heroicon-o-arrow-up-tray class="w-5 h-5" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">{{ $isWithdraw ? 'Tarik Deviden' : 'Suntik Modal' }}</p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                {{ Carbon::parse($mutasi->date)->translatedFormat('d M Y') }} • {{ $mutasi->account->name }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-sm {{ $isWithdraw ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $isWithdraw ? '+' : '-' }}Rp {{ number_format($mutasi->amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                                    <x-heroicon-o-inbox class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                    <p class="text-xs">Belum ada riwayat mutasi.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($showWalletModal)
                        <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-zinc-900/50 backdrop-blur-sm transition-opacity">
                            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-1">Buat Dompet Pribadi</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Contoh: BCA Istri, Kas Utama, dll.</p>

                                <form wire:submit="saveWallet" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Dompet</label>
                                        <input type="text" wire:model="newWalletName" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Saldo Awal (Opsional)</label>
                                        <input type="number" wire:model="newWalletBalance" min="0" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm" placeholder="0">
                                    </div>
                                    <div class="flex gap-3 mt-6">
                                        <button type="button" wire:click="$set('showWalletModal', false)" class="flex-1 py-2.5 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                                        <button type="submit" class="flex-1 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-bold text-sm transition shadow-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                </div>

            @elseif($currentTab === 'wallet')
                <div class="animate-fade-in space-y-6 relative">
                    
                    <div class="flex justify-between items-end">
                        <div class="flex flex-col gap-1">
                            <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Dompet Pribadi</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Kelola rekening dan uang kas rumah tangga.</p>
                        </div>
                        
                        <button wire:click="$set('showWalletModal', true)" class="p-2 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-xl hover:bg-green-200 dark:hover:bg-green-800 transition">
                            <x-heroicon-o-plus class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="bg-zinc-800 dark:bg-zinc-900 rounded-3xl p-6 text-white shadow-lg border border-zinc-700 dark:border-zinc-800 relative overflow-hidden flex items-center justify-between">
                        <div class="relative z-10">
                            <p class="text-zinc-400 text-xs font-bold uppercase tracking-wider mb-1">Total Saldo Gabungan</p>
                            <h3 class="text-3xl font-extrabold tracking-tight text-green-400">
                                Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}
                            </h3>
                        </div>
                        <div class="p-3 bg-zinc-700 dark:bg-zinc-800 rounded-2xl opacity-50">
                            <x-heroicon-o-wallet class="w-8 h-8 text-zinc-300" />
                        </div>
                        <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-green-500 opacity-20 rounded-full blur-2xl"></div>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider mb-3">Daftar Rekening & Kas</h3>
                        
                        <div class="space-y-3">
                            @forelse($personalWallets as $wallet)
                                <div wire:click="viewWallet({{ $wallet->id }})" class="cursor-pointer bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 flex items-center justify-between group hover:border-green-300 dark:hover:border-green-700 hover:shadow-md transition-all active:scale-95">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-zinc-100 dark:bg-zinc-700/50 text-zinc-600 dark:text-zinc-300 rounded-xl group-hover:bg-green-50 dark:group-hover:bg-green-900/40 group-hover:text-green-600 dark:group-hover:text-green-400 transition">
                                            <x-heroicon-o-credit-card class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-zinc-800 dark:text-zinc-100 text-base">{{ $wallet->name }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Ketuk untuk lihat mutasi</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-0.5">Saldo Aktif</p>
                                        <p class="font-bold text-lg text-green-600 dark:text-green-400">
                                            Rp {{ number_format($wallet->current_balance, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10 bg-white dark:bg-zinc-800 rounded-3xl border-2 border-dashed border-zinc-200 dark:border-zinc-700">
                                    <x-heroicon-o-wallet class="w-10 h-10 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                                    <p class="text-sm font-bold text-zinc-600 dark:text-zinc-300">Belum ada dompet pribadi.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @if($showWalletModal)
                        <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 bg-zinc-900/50 backdrop-blur-sm transition-opacity">
                            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-1">Buat Dompet Pribadi</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Contoh: BCA Istri, Kas Utama, dll.</p>

                                <form wire:submit="saveWallet" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Dompet</label>
                                        <input type="text" wire:model="newWalletName" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Saldo Awal (Opsional)</label>
                                        <input type="number" wire:model="newWalletBalance" min="0" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm" placeholder="0">
                                    </div>
                                    <div class="flex gap-3 mt-6">
                                        <button type="button" wire:click="$set('showWalletModal', false)" class="flex-1 py-2.5 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                                        <button type="submit" class="flex-1 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-bold text-sm transition shadow-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if($viewingWalletId && $viewingWallet)
                        <div class="fixed inset-0 z-[70] flex flex-col justify-end bg-zinc-900/60 backdrop-blur-sm transition-opacity">
                            <div class="absolute inset-0" wire:click="closeWallet"></div>
                            
                            <div class="relative bg-zinc-50 dark:bg-zinc-900 w-full max-w-md mx-auto rounded-t-3xl shadow-2xl border-t border-zinc-200 dark:border-zinc-800 flex flex-col max-h-[85vh] animate-slide-up">
                                
                                <div class="p-5 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-white dark:bg-zinc-800 rounded-t-3xl sticky top-0 z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-xl">
                                            <x-heroicon-o-credit-card class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100">{{ $viewingWallet->name }}</h3>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Mutasi Rekening</p>
                                        </div>
                                    </div>
                                    <button wire:click="closeWallet" class="p-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded-full hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                                        <x-heroicon-o-x-mark class="w-5 h-5" />
                                    </button>
                                </div>

                                <div class="p-5 overflow-y-auto pb-safe">
                                    <div class="space-y-3">
                                        @forelse($walletMutations as $mutasi)
                                            @php
                                                $isIncome = $mutasi->category->type === 'income';
                                            @endphp
                                            <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 flex justify-between items-center">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 rounded-xl {{ $isIncome ? 'bg-green-100 dark:bg-green-900/40 text-green-600' : 'bg-red-100 dark:bg-red-900/40 text-red-600' }}">
                                                        @if($isIncome)
                                                            <x-heroicon-o-arrow-down-left class="w-5 h-5" />
                                                        @else
                                                            <x-heroicon-o-arrow-up-right class="w-5 h-5" />
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100 line-clamp-1">{{ $mutasi->category->name }}</p>
                                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                            {{ Carbon::parse($mutasi->date)->translatedFormat('d M Y') }} 
                                                            @if($mutasi->description) • {{ Str::limit($mutasi->description, 20) }} @endif
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right whitespace-nowrap ml-2">
                                                    <p class="font-bold text-sm {{ $isIncome ? 'text-green-600 dark:text-green-400' : 'text-zinc-800 dark:text-zinc-100' }}">
                                                        {{ $isIncome ? '+' : '-' }}Rp {{ number_format($mutasi->amount, 0, ',', '.') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                                                <x-heroicon-o-document-text class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p class="text-xs font-medium">Belum ada transaksi di dompet ini.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
                
            @elseif($currentTab === 'target')
                <div class="animate-fade-in space-y-6 relative pb-10">
                    
                    <div class="flex justify-between items-end">
                        <div class="flex flex-col gap-1">
                            <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Target & Impian</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Rencanakan hasil keringat bisnismu.</p>
                        </div>
                        
                        <button wire:click="$set('showGoalModal', true)" class="p-2 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-xl hover:bg-green-200 dark:hover:bg-green-800 transition">
                            <x-heroicon-o-plus class="w-6 h-6" />
                        </button>
                    </div>

                    @if (session()->has('message'))
                        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 shadow-sm border border-green-200 dark:border-green-800">
                            <x-heroicon-o-check-circle class="w-6 h-6" />
                            <p class="text-sm font-bold">{{ session('message') }}</p>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider mb-3">Sedang Berjalan</h3>
                        
                        <div>
                        <div class="flex justify-between items-end mb-3">
                            <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                                <x-heroicon-o-home-modern class="w-5 h-5 text-red-500" />
                                Kebutuhan Pokok
                            </h3>
                            <div class="text-right">
                                <p class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 tracking-wider">TOTAL KEBUTUHAN</p>
                                <p class="text-xs font-extrabold text-red-600 dark:text-red-400">
                                    Rp {{ number_format($totalMonthlyCurrent, 0, ',', '.') }} 
                                    <span class="text-zinc-400 dark:text-zinc-500 font-medium">/ {{ number_format($totalMonthlyTarget, 0, ',', '.') }}</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            @forelse($monthlyNeeds as $goal)
                                @php
                                    $percentage = $goal->target_amount > 0 ? min(100, round(($goal->current_amount / $goal->target_amount) * 100)) : 0;
                                @endphp
                                <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border-l-4 border-l-red-500 border border-zinc-100 dark:border-zinc-700">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <p class="font-bold text-zinc-800 dark:text-zinc-100 text-lg">{{ $goal->name }}</p>
                                            <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 mt-0.5">Wajib dipenuhi setiap bulan</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button wire:click="openExpenseModal({{ $goal->id }})" class="p-2 bg-zinc-50 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-red-50 hover:text-red-600 rounded-xl transition tooltip-trigger" title="Realisasi / Bayar">
                                                <x-heroicon-o-shopping-bag class="w-5 h-5" />
                                            </button>
                                            
                                            <button wire:click="openAddFund({{ $goal->id }})" class="p-2 bg-zinc-50 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-green-50 hover:text-green-600 rounded-xl transition tooltip-trigger" title="Isi Tabungan">
                                                <x-heroicon-o-currency-dollar class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-end mb-2">
                                        <div>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5">Terkumpul</p>
                                            <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5">Target</p>
                                            <p class="font-bold text-sm text-zinc-500 dark:text-zinc-500">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</p>
                                        </div>
                                    </div>

                                    <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                        <div class="{{ $percentage >= 100 ? 'bg-green-500' : 'bg-red-500' }} h-2.5 rounded-full transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 bg-white dark:bg-zinc-800 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-700">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Belum ada target pokok (Contoh: SPP, Dapur, Listrik).</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="flex justify-between items-end mb-3">
                            <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                                <x-heroicon-o-star class="w-5 h-5 text-amber-500" />
                                Impian & Tabungan
                            </h3>
                            <div class="text-right">
                                <p class="text-[9px] font-bold text-zinc-400 dark:text-zinc-500 tracking-wider">TOTAL IMPIAN</p>
                                <p class="text-xs font-extrabold text-green-600 dark:text-green-400">
                                    Rp {{ number_format($totalDreamCurrent, 0, ',', '.') }} 
                                    <span class="text-zinc-400 dark:text-zinc-500 font-medium">/ {{ number_format($totalDreamTarget, 0, ',', '.') }}</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            @forelse($dreamGoals as $goal)
                                @php
                                    $percentage = $goal->target_amount > 0 ? min(100, round(($goal->current_amount / $goal->target_amount) * 100)) : 0;
                                @endphp
                                <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 group">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <p class="font-bold text-zinc-800 dark:text-zinc-100 text-lg">{{ $goal->name }}</p>
                                            @if($goal->deadline)
                                                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 flex items-center gap-1 mt-0.5">
                                                    <x-heroicon-o-clock class="w-3 h-3" /> Target: {{ \Carbon\Carbon::parse($goal->deadline)->translatedFormat('d M Y') }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="flex gap-2">
                                            <button wire:click="openExpenseModal({{ $goal->id }})" class="p-2 bg-zinc-50 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-red-50 hover:text-red-600 rounded-xl transition tooltip-trigger" title="Realisasi / Bayar">
                                                <x-heroicon-o-shopping-bag class="w-5 h-5" />
                                            </button>
                                            
                                            <button wire:click="openAddFund({{ $goal->id }})" class="p-2 bg-zinc-50 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-green-50 hover:text-green-600 rounded-xl transition tooltip-trigger" title="Isi Tabungan">
                                                <x-heroicon-o-currency-dollar class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-end mb-2">
                                        <div>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5">Terkumpul</p>
                                            <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-sm text-zinc-500 dark:text-zinc-500">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</p>
                                        </div>
                                    </div>

                                    <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-green-500 h-2.5 rounded-full transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 bg-white dark:bg-zinc-800 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-700">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Belum ada target impian (Contoh: Mobil, Liburan).</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($achievedGoals->count() > 0)
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 class="text-sm font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-3">Impian Tercapai 🎉</h3>
                            <div class="space-y-3 opacity-75">
                                @foreach($achievedGoals as $goal)
                                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full">
                                                <x-heroicon-o-check-badge class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <p class="font-bold text-zinc-600 dark:text-zinc-300 line-through">{{ $goal->name }}</p>
                                                <p class="text-xs text-zinc-500">Sukses terkumpul Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($showGoalModal)
                        <div class="fixed inset-0 z-[80] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity">
                            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-1">Target Baru</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Tuliskan impian besarmu.</p>

                                <form wire:submit="saveGoal" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Impian</label>
                                        <input type="text" wire:model="goalName" required placeholder="Cth: Liburan ke Jepang" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Target Dana (Rp)</label>
                                        <input type="number" wire:model="goalTargetAmount" required min="1" placeholder="20000000" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Tenggat Waktu (Opsional)</label>
                                        <input type="date" wire:model="goalDeadline" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Tipe Target</label>
                                        <select wire:model="goalType" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-green-500 text-sm font-bold">
                                            <option value="monthly">Kebutuhan Pokok (SPP, Belanja, Listrik)</option>
                                            <option value="dream">Impian / Tabungan (Rumah, Mobil, Liburan)</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-3 mt-6">
                                        <button type="button" wire:click="$set('showGoalModal', false)" class="flex-1 py-2.5 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                                        <button type="submit" class="flex-1 py-2.5 rounded-xl text-white bg-green-600 hover:bg-green-700 font-bold text-sm transition shadow-sm">Mulai Menabung</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if($showAddFundModal)
                        <div class="fixed inset-0 z-[90] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity">
                            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <x-heroicon-o-currency-dollar class="w-6 h-6" />
                                </div>
                                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">Isi Tabungan</h3>
                                <p class="text-xs text-center text-zinc-500 dark:text-zinc-400 mb-6">Masukkan nominal yang disisihkan.</p>

                                <form wire:submit="saveFund" class="space-y-4">
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                                        <input type="number" wire:model="fundAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-4 pl-10 focus:ring-2 focus:ring-green-500 font-extrabold text-xl text-center shadow-inner" placeholder="0">
                                    </div>
                                    <div class="flex gap-3 mt-6">
                                        <button type="button" wire:click="$set('showAddFundModal', false)" class="flex-1 py-3 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Nanti Dulu</button>
                                        <button type="submit" class="flex-1 py-3 rounded-xl text-white bg-green-600 hover:bg-green-700 font-bold text-sm transition shadow-sm">Simpan Uang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($showExpenseModal)
                        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity">
                            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mb-4 mx-auto">
                                    <x-heroicon-o-receipt-percent class="w-6 h-6" />
                                </div>
                                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">
                                    {{ $expenseGoalId ? 'Realisasi Target' : 'Catat Pengeluaran' }}
                                </h3>
                                <p class="text-xs text-center text-zinc-500 dark:text-zinc-400 mb-6">Uang akan ditarik dari dompet pribadi.</p>

                                <form wire:submit="saveExpense" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Dari Dompet / Rekening</label>
                                        <select wire:model="expenseAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-red-500 text-sm font-semibold">
                                            <option value="">-- Pilih Sumber Dana --</option>
                                            @foreach($personalAccounts as $acc) 
                                                <option value="{{ $acc->id }}">{{ $acc->name }}</option> 
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal Keluar (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                                            <input type="number" wire:model="expenseAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 pl-10 focus:ring-2 focus:ring-red-500 font-extrabold text-lg shadow-inner" placeholder="0">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Untuk Keperluan</label>
                                        <input type="text" wire:model="expenseNotes" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-red-500 text-sm" placeholder="Makan malam, Bensin, dll...">
                                    </div>

                                    <div class="flex gap-3 mt-6">
                                        <button type="button" wire:click="$set('showExpenseModal', false)" class="flex-1 py-3 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                                        <button type="submit" class="flex-1 py-3 rounded-xl text-white bg-red-600 hover:bg-red-700 font-bold text-sm transition shadow-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

        </div>

        <div class="fixed bottom-0 left-0 w-full bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-end px-2 pb-safe pt-2 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] dark:shadow-none z-50 transition-colors duration-300">
            
            <button wire:click="setTab('home')" class="flex flex-col items-center p-2 w-14 transition {{ $currentTab === 'home' ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                <x-heroicon-o-home class="w-6 h-6 mb-1" />
                <span class="text-[10px] font-medium">Home</span>
            </button>

            <button wire:click="setTab('business')" class="flex flex-col items-center p-2 w-14 transition {{ $currentTab === 'business' ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                <x-heroicon-o-building-storefront class="w-6 h-6 mb-1" />
                <span class="text-[10px] font-medium">Bisnis</span>
            </button>

            <div class="relative -top-5 w-14 flex justify-center">
                <button wire:click="setTab('transfer')" class="flex items-center justify-center w-14 h-14 bg-green-600 dark:bg-green-500 text-white rounded-full shadow-lg hover:bg-green-700 dark:hover:bg-green-400 transition transform hover:scale-105 border-4 border-zinc-50 dark:border-zinc-900 focus:outline-none">
                    <x-heroicon-o-arrows-right-left class="w-6 h-6" />
                </button>
            </div>

            <button wire:click="setTab('wallet')" class="flex flex-col items-center p-2 w-14 transition {{ $currentTab === 'wallet' ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                <x-heroicon-o-wallet class="w-6 h-6 mb-1" />
                <span class="text-[10px] font-medium">Dompet</span>
            </button>

            <button wire:click="setTab('target')" class="flex flex-col items-center p-2 w-14 transition {{ $currentTab === 'target' ? 'text-green-600 dark:text-green-400 scale-110' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                <x-heroicon-o-trophy class="w-6 h-6 mb-1" />
                <span class="text-[10px] font-medium">Target</span>
            </button>

        </div>
    </div>
</div>
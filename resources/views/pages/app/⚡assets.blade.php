<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Goal;
use App\Models\Category;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Str;

new #[Layout('layouts::pwa')] class extends Component {
    
    public string $assetTab = 'wallet'; 

    // --- STATE: DOMPET ---
    public bool $showWalletModal = false;
    public string $newWalletName = '';
    public string $newWalletBalance = '';
    public ?int $viewingWalletId = null;

    // --- STATE: BUDGET LIMIT ---
    public bool $showBudgetModal = false;
    public ?int $budgetCategoryId = null;
    public string $budgetAmount = '';

    // --- STATE: JALAN PINTAS CATAT PENGELUARAN BUDGET ---
    public bool $showBudgetExpenseModal = false;
    public ?int $budgetExpenseCategoryId = null;
    public string $budgetExpenseCategoryName = '';
    public string $budgetExpenseAmount = '';
    public ?int $budgetExpenseAccountId = null;
    public string $budgetExpenseNotes = '';

    // --- STATE: GOALS ---
    public bool $showGoalModal = false;
    public string $goalName = '';
    public string $goalTargetAmount = '';
    public string $goalDeadline = '';

    // --- STATE: TOP UP GOAL ---
    public bool $showAddFundModal = false;
    public ?int $fundGoalId = null;
    public string $fundAmount = '';

    // --- STATE: REALISASI GOAL (Belanja Impian) ---
    public bool $showRealizeModal = false;
    public ?int $realizeGoalId = null;
    public string $realizeAmount = '';
    public ?int $realizeAccountId = null;
    public string $realizeNotes = '';

    // --- STATE: TARIK DARURAT TABUNGAN (Kembali ke Kas Free) ---
    public bool $showEmergencyWithdrawModal = false;
    public ?int $emergencyGoalId = null;
    public string $emergencyGoalName = '';
    public string $emergencyAmount = '';

    // --- STATE EDIT ---
    public ?int $editBudgetId = null;
    public ?int $editGoalId = null;

    public bool $showIncomeModal = false;
    public string $incomeAmount = '';
    public string $incomeSource = '';
    public ?int $incomeAccountId = null;

    public function setTab($tab)
    {
        $this->assetTab = $tab;
    }

    // ==========================================
    // LOGIC DOMPET
    // ==========================================
    public function saveWallet()
    {
        $this->validate([
            'newWalletName' => 'required|string|max:255',
            'newWalletBalance' => 'required|numeric|min:0',
        ]);

        Account::create([
            'business_id' => null,
            'user_id' => Auth::id(),
            'name' => $this->newWalletName,
            'opening_balance' => $this->newWalletBalance,
        ]);

        $this->reset(['showWalletModal', 'newWalletName', 'newWalletBalance']);
        session()->flash('message', 'Dompet berhasil ditambahkan!');
    }

    public function viewWallet($id)
    {
        $this->viewingWalletId = $id;
    }

    public function closeWalletView()
    {
        $this->viewingWalletId = null;
    }

    public function openIncomeModal()
    {
        $this->reset(['incomeAmount', 'incomeSource', 'incomeAccountId']);
        $this->showIncomeModal = true;
    }

    public function saveIncome()
    {
        $this->validate([
            'incomeAmount' => 'required|numeric|min:1',
            'incomeSource' => 'required|string|max:255',
            'incomeAccountId' => 'required|exists:accounts,id',
        ]);

        $user = Auth::user();

        $category = Category::firstOrCreate([
            'business_id' => null,
            'user_id' => $user->id,
            'name' => 'Pemasukan Lainnya (Gaji, Hadiah, dll)',
            'type' => 'income',
            'group' => 'personal'
        ]);

        Transaction::create([
            'business_id' => null,
            'user_id' => $user->id,
            'account_id' => $this->incomeAccountId,
            'category_id' => $category->id,
            'amount' => $this->incomeAmount,
            'date' => now(),
            'description' => $this->incomeSource,
        ]);

        $this->reset(['showIncomeModal', 'incomeAmount', 'incomeSource', 'incomeAccountId']);
        session()->flash('message', 'Pemasukan berhasil dicatat! Saldo dompet Anda telah bertambah.');
    }

    // ==========================================
    // LOGIC BUDGET RUTIN
    // ==========================================
    public function saveBudget()
    {
        $this->validate([
            'budgetCategoryId' => 'required|exists:categories,id',
            'budgetAmount' => 'required|numeric|min:1',
        ]);

        $existing = Budget::where('user_id', Auth::id())->where('category_id', $this->budgetCategoryId)->first();

        if ($existing) {
            $existing->update(['amount' => $this->budgetAmount]);
            session()->flash('message', 'Anggaran berhasil diperbarui!');
        } else {
            Budget::create([
                'user_id' => Auth::id(),
                'category_id' => $this->budgetCategoryId,
                'amount' => $this->budgetAmount,
            ]);
            session()->flash('message', 'Anggaran baru berhasil dibuat!');
        }

        $this->reset(['showBudgetModal', 'budgetCategoryId', 'budgetAmount']);
    }

    public function openBudgetExpense($categoryId, $categoryName)
    {
        $this->budgetExpenseCategoryId = $categoryId;
        $this->budgetExpenseCategoryName = $categoryName;
        $this->budgetExpenseAmount = '';
        $this->budgetExpenseAccountId = null;
        $this->budgetExpenseNotes = '';
        $this->showBudgetExpenseModal = true;
    }

    public function saveBudgetExpense()
    {
        $this->validate([
            'budgetExpenseAmount' => 'required|numeric|min:1',
            'budgetExpenseAccountId' => 'required',
            'budgetExpenseNotes' => 'nullable|string|max:255',
        ]);

        Transaction::create([
            'business_id' => null,
            'user_id' => Auth::id(),
            'account_id' => $this->budgetExpenseAccountId,
            'category_id' => $this->budgetExpenseCategoryId,
            'amount' => $this->budgetExpenseAmount,
            'date' => now(),
            'description' => $this->budgetExpenseNotes ?: 'Pengeluaran rutin: ' . $this->budgetExpenseCategoryName,
        ]);

        $this->reset(['showBudgetExpenseModal', 'budgetExpenseCategoryId', 'budgetExpenseCategoryName', 'budgetExpenseAmount', 'budgetExpenseAccountId', 'budgetExpenseNotes']);
        session()->flash('message', 'Pengeluaran berhasil dicatat dan masuk ke riwayat!');
    }

    // --- LOGIC EDIT/DELETE BUDGET ---
    public function editBudget($id)
    {
        $budget = Budget::find($id);
        if ($budget) {
            $this->editBudgetId = $budget->id;
            $this->budgetCategoryId = $budget->category_id;
            $this->budgetAmount = $budget->amount;
            $this->showBudgetModal = true;
        }
    }

    public function deleteBudget($id)
    {
        Budget::destroy($id);
        session()->flash('message', 'Anggaran berhasil dihapus!');
    }

    // --- LOGIC EDIT/DELETE GOAL ---
    public function editGoal($id)
    {
        $goal = Goal::find($id);
        if ($goal) {
            $this->editGoalId = $goal->id;
            $this->goalName = $goal->name;
            $this->goalTargetAmount = $goal->target_amount;
            $this->goalDeadline = $goal->deadline;
            $this->showGoalModal = true;
        }
    }

    public function deleteGoal($id)
    {
        Goal::destroy($id);
        session()->flash('message', 'Impian dihapus. Saldo yang terkunci otomatis kembali ke Kas Free!');
    }

    // ==========================================
    // LOGIC IMPIAN (GOALS)
    // ==========================================
    public function saveGoal()
    {
        $this->validate([
            'goalName' => 'required|string|max:255',
            'goalTargetAmount' => 'required|numeric|min:1',
            'goalDeadline' => 'nullable|date',
        ]);

        if ($this->editGoalId) {
            $goal = Goal::find($this->editGoalId);
            $goal->update([
                'name' => $this->goalName,
                'target_amount' => $this->goalTargetAmount,
                'deadline' => $this->goalDeadline,
            ]);
            session()->flash('message', 'Target impian berhasil diupdate!');
        } else {
            Goal::create([
                'user_id' => Auth::id(),
                'name' => $this->goalName,
                'type' => 'dream',
                'target_amount' => $this->goalTargetAmount,
                'current_amount' => 0,
                'deadline' => $this->goalDeadline,
                'status' => 'active',
            ]);
            session()->flash('message', 'Target impian berhasil dibuat!');
        }

        $this->reset(['showGoalModal', 'goalName', 'goalTargetAmount', 'goalDeadline', 'editGoalId']);
    }

    // TOP UP
    public function openAddFund($id)
    {
        $this->fundGoalId = $id;
        $this->fundAmount = '';
        $this->showAddFundModal = true;
    }

    public function saveFund()
    {
        $this->validate(['fundAmount' => 'required|numeric|min:1']);
        $goal = Goal::find($this->fundGoalId);
        if ($goal) {
            $goal->current_amount += $this->fundAmount;
            if ($goal->current_amount >= $goal->target_amount) $goal->status = 'achieved';
            $goal->save();
            session()->flash('message', 'Sukses mengunci dana untuk ' . $goal->name . '!');
        }
        $this->reset(['showAddFundModal', 'fundGoalId', 'fundAmount']);
    }

    // TARIK DARURAT (Kembali ke Kas Free)
    public function openEmergencyWithdraw($id, $name)
    {
        $this->emergencyGoalId = $id;
        $this->emergencyGoalName = $name;
        $this->emergencyAmount = '';
        $this->showEmergencyWithdrawModal = true;
    }

    public function saveEmergencyWithdraw()
    {
        $this->validate(['emergencyAmount' => 'required|numeric|min:1']);
        $goal = Goal::find($this->emergencyGoalId);
        if ($goal) {
            if ($this->emergencyAmount > $goal->current_amount) {
                $this->addError('emergencyAmount', 'Nominal tarikan melebihi saldo yang terkunci!');
                return;
            }
            $goal->current_amount -= $this->emergencyAmount;
            if ($goal->current_amount < 0) $goal->current_amount = 0;
            if ($goal->status === 'achieved' && $goal->current_amount < $goal->target_amount) {
                $goal->status = 'active';
            }
            $goal->save();
            session()->flash('message', 'Dana darurat berhasil dilepas ke Kas Free!');
        }
        $this->reset(['showEmergencyWithdrawModal', 'emergencyGoalId', 'emergencyGoalName', 'emergencyAmount']);
    }

    // REALISASI (BELANJA FISIK)
    public function openRealize($id)
    {
        $goal = Goal::find($id);
        $this->realizeGoalId = $id;
        $this->realizeAmount = $goal->current_amount > 0 ? $goal->current_amount : '';
        $this->realizeNotes = 'Realisasi Impian: ' . $goal->name;
        $this->showRealizeModal = true;
    }

    public function saveRealize()
    {
        $this->validate([
            'realizeAmount' => 'required|numeric|min:1',
            'realizeAccountId' => 'required',
            'realizeNotes' => 'required|string|max:255',
        ]);

        $goal = Goal::find($this->realizeGoalId);
        $category = Category::where('name', 'Realisasi Target & Impian')->first();

        if ($goal && $category) {
            Transaction::create([
                'business_id' => null,
                'user_id' => Auth::id(),
                'account_id' => $this->realizeAccountId,
                'category_id' => $category->id,
                'amount' => $this->realizeAmount,
                'date' => now(),
                'description' => $this->realizeNotes,
            ]);

            $goal->current_amount -= $this->realizeAmount;
            if ($goal->current_amount <= 0) $goal->current_amount = 0;
            $goal->save();

            session()->flash('message', 'Impian berhasil direalisasikan!');
        }

        $this->reset(['showRealizeModal', 'realizeGoalId', 'realizeAmount', 'realizeAccountId', 'realizeNotes']);
    }

    public function with(): array
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];
        $thisMonth = Carbon::now()->startOfMonth();

        // 1. DATA DOMPET
        $personalWallets = Account::whereNull('business_id')->whereIn('user_id', $familyIds)->get()->map(function ($acc) {
            $income = Transaction::where('account_id', $acc->id)->whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
            $expense = Transaction::where('account_id', $acc->id)->whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
            $acc->current_balance = $acc->opening_balance + $income - $expense;
            return $acc;
        });

        $totalWalletBalance = $personalWallets->sum('current_balance');

        $walletMutations = collect();
        $viewingWallet = null;
        if ($this->viewingWalletId) {
            $viewingWallet = Account::find($this->viewingWalletId);
            $walletMutations = Transaction::where('account_id', $this->viewingWalletId)->with('category')
                ->orderBy('date', 'desc')->orderBy('id', 'desc')->limit(20)->get();
        }

        // 2. DATA GOALS (TABUNGAN TERKUNCI)
        $dreamGoals = Goal::whereIn('user_id', $familyIds)->where('status', 'active')->orderBy('deadline', 'asc')->orderBy('id', 'desc')->get();
        $totalDreamTarget = $dreamGoals->sum('target_amount');
        $totalDreamCurrent = $dreamGoals->sum('current_amount');
        $achievedGoals = Goal::whereIn('user_id', $familyIds)->where('status', 'achieved')->orderBy('updated_at', 'desc')->get();

        // MENGHITUNG KAS FREE (UANG BEBAS) = TOTAL FISIK - TOTAL TABUNGAN TERKUNCI
        $kasFree = $totalWalletBalance - $totalDreamCurrent;
        if ($kasFree < 0) $kasFree = 0; // Fallback jika anomali data

        // 3. DATA BUDGETS (ANGGARAN RUTIN)
        $budgets = Budget::whereIn('user_id', $familyIds)->with('category')->get()->map(function($b) use ($familyIds, $thisMonth) {
            $spent = Transaction::whereIn('user_id', $familyIds)
                ->where('category_id', $b->category_id)
                ->where('date', '>=', $thisMonth)
                ->sum('amount');
            
            $b->spent = $spent;
            // PERBAIKAN: Hapus min(100) supaya kalau boros bisa terdeteksi 120% dll
            $b->percentage = $b->amount > 0 ? round(($spent / $b->amount) * 100) : 0; 
            return $b;
        });

        $totalBudgetAmount = $budgets->sum('amount');
        $totalBudgetSpent = $budgets->sum('spent');

        $expenseCategories = Category::where('group', 'personal')->where('type', 'expense')->orderBy('name')->get();

        return [
            'personalWallets' => $personalWallets,
            'totalWalletBalance' => $totalWalletBalance,
            'kasFree' => $kasFree,
            'viewingWallet' => $viewingWallet,
            'walletMutations' => $walletMutations,
            
            'budgets' => $budgets,
            'totalBudgetAmount' => $totalBudgetAmount,
            'totalBudgetSpent' => $totalBudgetSpent,
            'expenseCategories' => $expenseCategories,
            
            'dreamGoals' => $dreamGoals,
            'totalDreamTarget' => $totalDreamTarget,
            'totalDreamCurrent' => $totalDreamCurrent,
            'achievedGoals' => $achievedGoals,
        ];
    }
};
?>

<div class="animate-fade-in space-y-6 pb-10">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-teal-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-green-500/20">
            <x-heroicon-s-wallet class="w-6 h-6" />
        </div>
        <div>
            <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Assets & Anggaran</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manajemen kekayaan & alokasi keluarga.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in-up border border-green-200 dark:border-green-800">
            <x-heroicon-o-check-circle class="w-6 h-6 shrink-0" />
            <p class="text-sm font-bold">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl flex w-full border border-zinc-200 dark:border-zinc-700 shadow-sm relative">
        <button wire:click="setTab('wallet')" class="flex-1 py-2 text-[11px] sm:text-xs font-bold rounded-xl transition-all {{ $assetTab === 'wallet' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Dompet Fisik
        </button>
        <button wire:click="setTab('budget')" class="flex-1 py-2 text-[11px] sm:text-xs font-bold rounded-xl transition-all {{ $assetTab === 'budget' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Anggaran Rutin
        </button>
        <button wire:click="setTab('goal')" class="flex-1 py-2 text-[11px] sm:text-xs font-bold rounded-xl transition-all {{ $assetTab === 'goal' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Target Impian
        </button>
    </div>

    @if($assetTab === 'wallet')
        <div class="animate-fade-in space-y-5">
            
            <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden border border-zinc-700">
                <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-green-500 opacity-20 rounded-full blur-2xl"></div>
                <div class="relative z-10 flex flex-col gap-4">
                    <div>
                        <p class="text-zinc-400 text-[10px] font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                            <x-heroicon-o-check-badge class="w-4 h-4 text-green-400" /> Uang Bebas (Kas Free)
                        </p>
                        <h3 class="text-4xl font-extrabold tracking-tight text-green-400">
                            Rp {{ number_format($kasFree, 0, ',', '.') }}
                        </h3>
                        <p class="text-[10px] text-zinc-400 mt-1">Uang nganggur yang aman untuk dipakai jajan/belanja rutin.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-zinc-700/50">
                        <div>
                            <p class="text-[9px] text-zinc-400 uppercase tracking-wider mb-0.5">Total Dana</p>
                            <p class="text-sm font-bold text-white">Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}</p>
                        </div>
                        <div class="pl-3 border-l border-zinc-700/50">
                            <p class="text-[9px] text-zinc-400 uppercase tracking-wider mb-0.5">Terkunci (Tabungan)</p>
                            <p class="text-sm font-bold text-amber-400">Rp {{ number_format($totalDreamCurrent, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="openIncomeModal" class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-bold text-sm transition shadow-sm flex items-center justify-center gap-2">
                <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                Catat Pemasukan (Gaji, Bonus, dll)
            </button>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">Rincian Saldo Bank</h3>
                    <button wire:click="$set('showWalletModal', true)" class="text-xs text-green-600 dark:text-green-400 font-bold hover:underline flex items-center gap-1">
                        <x-heroicon-o-plus class="w-4 h-4" /> Tambah
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($personalWallets as $wallet)
                        <div wire:click="viewWallet({{ $wallet->id }})" class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 flex justify-between items-center cursor-pointer hover:border-green-500 transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                                    <x-heroicon-o-wallet class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">{{ $wallet->name }}</p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Ketuk untuk mutasi</p>
                                </div>
                            </div>
                            <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">
                                Rp {{ number_format($wallet->current_balance, 0, ',', '.') }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">Belum ada dompet terdaftar.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($showIncomeModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <x-heroicon-o-arrow-down-tray class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">
                    Catat Pemasukan Pribadi
                </h3>
                <p class="text-xs text-center text-zinc-500 dark:text-zinc-400 mb-6">Uang akan ditambahkan langsung ke Kas Free Anda.</p>

                <form wire:submit="saveIncome" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Simpan ke Dompet</label>
                        <select wire:model="incomeAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm font-semibold">
                            <option value="">-- Pilih Rekening / Dompet --</option>
                            @foreach($personalWallets as $acc) 
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option> 
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal Masuk (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                            <input type="number" wire:model="incomeAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3 pl-10 focus:ring-2 focus:ring-green-500 font-extrabold text-lg shadow-inner" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Sumber Dana / Catatan</label>
                        <input type="text" wire:model="incomeSource" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-sm" placeholder="Gaji bulan ini, Dikasih mertua, dll...">
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showIncomeModal', false)" class="flex-1 py-3 rounded-xl text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 font-bold text-sm transition">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl text-white bg-green-600 hover:bg-green-700 font-bold text-sm transition shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($assetTab === 'budget')
        <div class="animate-fade-in space-y-5">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-700 flex justify-between items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-24 h-full bg-blue-50 dark:bg-blue-900/20 rounded-l-full blur-xl"></div>
                <div>
                    <p class="text-zinc-500 dark:text-zinc-400 text-[10px] font-bold uppercase tracking-wider mb-1">Total Terpakai Bulan Ini</p>
                    <h3 class="text-2xl font-extrabold text-blue-500">
                        Rp {{ number_format($totalBudgetSpent, 0, ',', '.') }} 
                    </h3>
                    <p class="text-xs text-zinc-400 mt-1">Dari limit Rp {{ number_format($totalBudgetAmount, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center z-10">
                    <x-heroicon-o-chart-bar-square class="w-6 h-6 text-blue-500" />
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">Tracker Pengeluaran</h3>
                    <button wire:click="$set('showBudgetModal', true)" class="text-xs text-blue-600 dark:text-blue-500 font-bold hover:underline flex items-center gap-1">
                        <x-heroicon-o-cog-6-tooth class="w-4 h-4" /> Atur Anggaran
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse($budgets as $budget)
                        @php
                            if ($budget->percentage < 75) {
                                $barColor = 'bg-green-500'; 
                                $textColor = 'text-green-600 dark:text-green-400';
                                $statusIcon = '📉';
                                $statusText = 'Kurang Banyak';
                            } elseif ($budget->percentage < 100) {
                                $barColor = 'bg-amber-500'; 
                                $textColor = 'text-amber-600 dark:text-amber-400';
                                $statusIcon = '⏳';
                                $statusText = 'Hampir Terpenuhi';
                            } elseif ($budget->percentage == 100) {
                                $barColor = 'bg-emerald-500';
                                $textColor = 'text-emerald-600 dark:text-emerald-400';
                                $statusIcon = '✅';
                                $statusText = 'Pas / Terpenuhi!';
                            } else {
                                $barColor = 'bg-red-500';
                                $textColor = 'text-red-600 dark:text-red-400';
                                $statusIcon = '⚠️';
                                $statusText = 'OverBudget!';
                            }
                        @endphp
                        
                        <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-zinc-800 dark:text-zinc-100">{{ $budget->category->name }}</p>
                                        
                                        <button wire:click="editBudget({{ $budget->id }})" class="text-zinc-400 hover:text-blue-500 transition">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteBudget({{ $budget->id }})" wire:confirm="Yakin ingin menghapus tracker anggaran ini?" class="text-zinc-400 hover:text-red-500 transition">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>

                                        <button wire:click="openBudgetExpense({{ $budget->category_id }}, '{{ addslashes($budget->category->name) }}')" class="p-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-500 hover:text-blue-600 rounded-md transition tooltip-trigger" title="Catat Pengeluaran Ini">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p class="text-[10px] font-bold {{ $textColor }} flex items-center gap-1 mt-0.5">
                                        {{ $statusIcon }} {{ $statusText }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">Rp {{ number_format($budget->spent, 0, ',', '.') }}</p>
                                    <p class="text-[10px] font-bold text-zinc-400">/ Rp {{ number_format($budget->amount, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden mt-3">
                                <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-1000" style="width: {{ min($budget->percentage, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium mb-2">Belum ada anggaran bulanan diatur.</p>
                            <button wire:click="$set('showBudgetModal', true)" class="text-xs px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-bold">Atur Sekarang</button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($assetTab === 'goal')
        <div class="animate-fade-in space-y-5">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-700 flex justify-between items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-24 h-full bg-amber-50 dark:bg-amber-900/20 rounded-l-full blur-xl"></div>
                <div>
                    <p class="text-zinc-500 dark:text-zinc-400 text-[10px] font-bold uppercase tracking-wider mb-1">Total Tabungan Impian</p>
                    <h3 class="text-2xl font-extrabold text-amber-500">
                        Rp {{ number_format($totalDreamCurrent, 0, ',', '.') }} 
                    </h3>
                    <p class="text-xs text-zinc-400 mt-1">Uang ini <strong class="text-rose-500">terkunci</strong> dari Kas Free.</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center z-10">
                    <x-heroicon-o-lock-closed class="w-6 h-6 text-amber-500" />
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">Sedang Berjalan</h3>
                    <button wire:click="$set('showGoalModal', true)" class="text-xs text-amber-600 dark:text-amber-500 font-bold hover:underline flex items-center gap-1">
                        <x-heroicon-o-plus class="w-4 h-4" /> Impian Baru
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse($dreamGoals as $goal)
                        @php $percentage = $goal->target_amount > 0 ? min(100, round(($goal->current_amount / $goal->target_amount) * 100)) : 0; @endphp
                        <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="font-bold text-zinc-800 dark:text-zinc-100 text-base">{{ $goal->name }}</p>
                                    <button wire:click="editGoal({{ $goal->id }})" class="text-zinc-400 hover:text-amber-500 transition">
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                    </button>
                                    <button wire:click="deleteGoal({{ $goal->id }})" wire:confirm="Yakin menghapus impian ini? Tabungan di dalamnya akan dikembalikan ke Kas Free." class="text-zinc-400 hover:text-red-500 transition">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                    @if($goal->deadline)
                                        <p class="text-[10px] font-bold text-amber-600 dark:text-amber-500 flex items-center gap-1 mt-0.5">
                                            <x-heroicon-o-clock class="w-3 h-3" /> Target: {{ Carbon::parse($goal->deadline)->translatedFormat('d M Y') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="openEmergencyWithdraw({{ $goal->id }}, '{{ addslashes($goal->name) }}')" class="p-2 bg-rose-50 dark:bg-rose-900/30 text-rose-600 hover:bg-rose-100 rounded-xl transition tooltip-trigger" title="Tarik Darurat ke Kas Free">
                                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                                    </button>
                                    <button wire:click="openRealize({{ $goal->id }})" class="p-2 bg-zinc-50 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 hover:text-zinc-800 rounded-xl transition tooltip-trigger" title="Realisasi / Belanja Fisik">
                                        <x-heroicon-o-shopping-bag class="w-5 h-5" />
                                    </button>
                                    <button wire:click="openAddFund({{ $goal->id }})" class="p-2 bg-amber-50 dark:bg-amber-900/30 text-amber-600 hover:bg-amber-100 rounded-xl transition tooltip-trigger" title="Kunci Saldo Baru (Top Up)">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-end mb-2">
                                <div>
                                    <p class="font-bold text-sm text-zinc-800 dark:text-zinc-100">Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-xs text-zinc-500 dark:text-zinc-500">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="w-full bg-zinc-100 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 font-medium">Belum ada impian. Yuk buat target pertamamu!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($showWalletModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4 text-center">Tambah Dompet Baru</h3>
                <form wire:submit="saveWallet" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Dompet</label>
                        <input type="text" wire:model="newWalletName" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Saldo Saat Ini (Rp)</label>
                        <input type="number" wire:model="newWalletBalance" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showWalletModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($viewingWalletId && $viewingWallet)
        <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-t-3xl sm:rounded-3xl w-full max-w-md shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up max-h-[80vh] flex flex-col">
                <div class="p-5 border-b border-zinc-100 dark:border-zinc-700 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50 rounded-t-3xl">
                    <div>
                        <h3 class="font-bold text-zinc-800 dark:text-zinc-100">{{ $viewingWallet->name }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Rp {{ number_format($viewingWallet->current_balance, 0, ',', '.') }}</p>
                    </div>
                    <button wire:click="closeWalletView" class="p-2 bg-zinc-200 dark:bg-zinc-700 rounded-full text-zinc-600 dark:text-zinc-300">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-5 overflow-y-auto space-y-4">
                    @forelse($walletMutations as $mut)
                        <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-700/50 pb-3 last:border-0 last:pb-0">
                            <div>
                                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $mut->category->name ?? 'Lainnya' }}</p>
                                <p class="text-[10px] text-zinc-500">{{ \Carbon\Carbon::parse($mut->date)->format('d M Y') }} • {{ Str::limit($mut->description, 20) }}</p>
                            </div>
                            <p class="text-sm font-bold {{ $mut->category && $mut->category->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-zinc-800 dark:text-zinc-300' }}">
                                {{ $mut->category && $mut->category->type === 'income' ? '+' : '-' }}Rp {{ number_format($mut->amount, 0, ',', '.') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-center text-sm text-zinc-500 py-4">Belum ada mutasi</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($showBudgetModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4 text-center">Atur Anggaran Rutin</h3>
                <form wire:submit="saveBudget" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Kategori Pengeluaran</label>
                        <select wire:model="budgetCategoryId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 text-sm font-semibold">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($expenseCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Limit Bulanan (Rp)</label>
                        <input type="number" wire:model="budgetAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 font-extrabold text-lg">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showBudgetModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showBudgetExpenseModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <x-heroicon-o-shopping-bag class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">Catat {{ $budgetExpenseCategoryName }}</h3>
                <p class="text-[10px] text-center text-zinc-500 mb-5">Otomatis update progres anggaranmu.</p>

                <form wire:submit="saveBudgetExpense" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Pakai Uang Dari Mana?</label>
                        <select wire:model="budgetExpenseAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">-- Pilih Dompet --</option>
                            @foreach($personalWallets as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal (Rp)</label>
                        <input type="number" wire:model="budgetExpenseAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-4 font-extrabold text-xl focus:ring-2 focus:ring-blue-500 text-center">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Catatan Tambahan</label>
                        <input type="text" wire:model="budgetExpenseNotes" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Opsional...">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showBudgetExpenseModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showGoalModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <h3 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4 text-center">Buat Target Impian</h3>
                <form wire:submit="saveGoal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nama Impian (Misal: Liburan)</label>
                        <input type="text" wire:model="goalName" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Target Nominal (Rp)</label>
                        <input type="number" wire:model="goalTargetAmount" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Target Selesai (Opsional)</label>
                        <input type="date" wire:model="goalDeadline" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 text-sm">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showGoalModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm">Buat Target</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showAddFundModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <x-heroicon-o-lock-closed class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">Kunci Saldo (Nabung)</h3>
                <p class="text-[10px] text-center text-zinc-500 mb-6">Uang ini akan diambil dari Kas Free mu.</p>

                <form wire:submit="saveFund" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal (Rp)</label>
                        <input type="number" wire:model="fundAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-4 text-xl font-extrabold focus:ring-2 focus:ring-amber-500 text-center">
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showAddFundModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm shadow-md">Kunci Saldo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showEmergencyWithdrawModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-4 mx-auto">
                    <x-heroicon-o-arrow-path class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">Tarik Darurat</h3>
                <p class="text-[10px] text-center text-zinc-500 mb-5">Tarik dana dari "{{ $emergencyGoalName }}" kembali menjadi uang nganggur (Kas Free).</p>

                <form wire:submit="saveEmergencyWithdraw" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal Tarik (Rp)</label>
                        <input type="number" wire:model="emergencyAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-4 text-xl font-extrabold focus:ring-2 focus:ring-rose-500 text-center">
                        @error('emergencyAmount') <span class="text-[10px] text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-3 mt-4">
                        <button type="button" wire:click="$set('showEmergencyWithdrawModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-md">Tarik ke Kas Free</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showRealizeModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up">
                <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 rounded-full flex items-center justify-center mb-4 mx-auto border border-zinc-200 dark:border-zinc-600">
                    <x-heroicon-o-shopping-bag class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-center text-zinc-800 dark:text-zinc-100 mb-1">Belanja Impian</h3>
                <p class="text-[10px] text-center text-zinc-500 mb-4">Uang fisik akan digesek & saldo impian ini akan hangus/lunas.</p>

                <form wire:submit="saveRealize" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Potong Dari Dompet Mana?</label>
                        <select wire:model="realizeAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-zinc-800 text-sm font-bold">
                            <option value="">-- Pilih --</option>
                            @foreach($personalWallets as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Nominal Terpakai (Rp)</label>
                        <input type="number" wire:model="realizeAmount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 font-extrabold focus:ring-2 focus:ring-zinc-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Catatan</label>
                        <input type="text" wire:model="realizeNotes" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-zinc-800 text-sm">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('showRealizeModal', false)" class="flex-1 py-3 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-bold text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-900 text-white font-bold text-sm shadow-md">Bayar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Business;
use Carbon\Carbon;

new #[Layout('layouts::pwa')] class extends Component {
    
    // TAB STATE: 'expense', 'withdraw', 'inject'
    public string $actionType = 'expense'; 
    
    // FORM STATE
    public ?int $businessId = null;
    public ?int $fromAccountId = null;
    public ?int $toAccountId = null;
    public ?int $categoryId = null;
    public string $amount = '';
    public string $notes = '';

    public function mount()
    {
        $user = Auth::user();
        $firstBusiness = $user->businesses()->first();
        if ($firstBusiness) {
            $this->businessId = $firstBusiness->id;
        }
    }

    public function setAction($type)
    {
        $this->actionType = $type;
        $this->reset(['fromAccountId', 'toAccountId', 'categoryId', 'amount', 'notes']);
        $this->resetValidation();
    }

    // --- LOGIC 1: SIMPAN PENGELUARAN PRIBADI ---
    public function submitExpense()
    {
        $this->validate([
            'fromAccountId' => 'required',
            'categoryId' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        Transaction::create([
            'business_id' => null,
            'user_id' => Auth::id(),
            'account_id' => $this->fromAccountId,
            'category_id' => $this->categoryId,
            'amount' => $this->amount,
            'date' => now(),
            'description' => $this->notes ?: 'Pengeluaran',
        ]);

        $this->reset(['amount', 'notes', 'fromAccountId', 'categoryId']);
        session()->flash('message', 'Pengeluaran rumah tangga berhasil dicatat!');
    }

    // --- LOGIC 2: SIMPAN LINTAS DOMAIN (WITHDRAW / INJECT) ---
    public function submitTransfer()
    {
        $this->validate([
            'businessId' => 'required',
            'fromAccountId' => 'required',
            'toAccountId' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        $business = Business::find($this->businessId);

        if ($this->actionType === 'withdraw') {
            // 1. Uang Keluar dari Bisnis
            $catBiz = Category::where('name', 'Penarikan Prive / Deviden')->first();
            Transaction::create([
                'business_id' => $business->id,
                'account_id' => $this->fromAccountId, 
                'category_id' => $catBiz->id,
                'amount' => $this->amount,
                'date' => now(),
                'description' => $this->notes ?: 'Penarikan ke rekening pribadi',
            ]);

            // 2. Uang Masuk ke Pribadi
            $catPersonal = Category::where('name', 'Hasil Bisnis (Prive / Deviden)')->first();
            Transaction::create([
                'business_id' => null,
                'user_id' => $user->id,
                'account_id' => $this->toAccountId, 
                'category_id' => $catPersonal->id,
                'amount' => $this->amount,
                'date' => now(),
                'description' => $this->notes ?: 'Pemasukan dari ' . $business->name,
            ]);

            session()->flash('message', 'Sukses menarik Prive dari toko!');
            
        } elseif ($this->actionType === 'inject') {
            // 1. Uang Keluar dari Pribadi
            $catPersonal = Category::where('name', 'Suntik Modal ke Bisnis')->first();
            Transaction::create([
                'business_id' => null,
                'user_id' => $user->id,
                'account_id' => $this->fromAccountId, 
                'category_id' => $catPersonal->id,
                'amount' => $this->amount,
                'date' => now(),
                'description' => $this->notes ?: 'Suntik modal ke ' . $business->name,
            ]);

            // 2. Uang Masuk ke Bisnis
            $catBiz = Category::where('name', 'Suntikan Modal Tambahan')->first();
            Transaction::create([
                'business_id' => $business->id,
                'account_id' => $this->toAccountId, 
                'category_id' => $catBiz->id,
                'amount' => $this->amount,
                'date' => now(),
                'description' => $this->notes ?: 'Suntikan modal dari pribadi',
            ]);

            session()->flash('message', 'Sukses menyuntik modal ke toko!');
        }

        $this->reset(['amount', 'notes', 'fromAccountId', 'toAccountId']);
    }

    public function with(): array
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];

        $userBusinesses = $user->businesses;

        $personalAccounts = Account::whereNull('business_id')->whereIn('user_id', $familyIds)->get();
        
        $businessAccounts = Account::where('business_id', $this->businessId)->get();
        
        $expenseCategories = Category::where('group', 'personal')
            ->where('type', 'expense')
            // ->whereIn('nature', ['need', 'want'])
            ->get();

        return [
            'userBusinesses' => $userBusinesses,
            'personalAccounts' => $personalAccounts,
            'businessAccounts' => $businessAccounts,
            'expenseCategories' => $expenseCategories,
        ];
    }
};
?>

<div class="animate-fade-in space-y-6 pb-10">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-amber-600 to-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
            <x-heroicon-s-arrows-right-left class="w-6 h-6" />
        </div>
        <div>
            <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Catat Transaksi</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pusat pergerakan uangmu.</p>
        </div>
    </div>
    

    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 shadow-sm border border-green-200 dark:border-green-800 animate-fade-in-up">
            <x-heroicon-o-check-circle class="w-6 h-6" />
            <p class="text-sm font-bold">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl flex relative w-full border border-zinc-200 dark:border-zinc-700 shadow-sm">
        <button wire:click="setAction('expense')" class="flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all {{ $actionType === 'expense' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Keluar Dapur
        </button>
        <button wire:click="setAction('withdraw')" class="flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all {{ $actionType === 'withdraw' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Tarik Untung
        </button>
        <button wire:click="setAction('inject')" class="flex-1 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all {{ $actionType === 'inject' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Suntik Modal
        </button>
    </div>

    @if($actionType === 'expense')
        <form wire:submit="submitExpense" class="bg-white dark:bg-zinc-800 p-6 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 space-y-5 animate-fade-in-up">
            <div class="w-12 h-12 bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-full flex items-center justify-center mb-2">
                <x-heroicon-o-shopping-bag class="w-6 h-6" />
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Kategori Pengeluaran</label>
                <select wire:model="categoryId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-green-500 font-semibold text-sm">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($expenseCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Dari Dompet (Uang Pribadi)</label>
                <select wire:model="fromAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-green-500 text-sm">
                    <option value="">-- Pilih Sumber Dana --</option>
                    @foreach($personalAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} - {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Nominal (Rp)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                    <input type="number" wire:model="amount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-4 pl-12 focus:ring-2 focus:ring-green-500 font-extrabold text-xl shadow-inner" placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Catatan Tambahan</label>
                <input type="text" wire:model="notes" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-green-500 text-sm" placeholder="Contoh: Beli beras 5kg...">
            </div>

            <button type="submit" class="w-full py-4 mt-2 bg-zinc-800 hover:bg-zinc-900 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-white rounded-xl font-bold text-sm transition-colors flex justify-center items-center gap-2 shadow-md">
                <x-heroicon-o-paper-airplane class="w-5 h-5 -mt-0.5" /> Simpan Pengeluaran
            </button>
        </form>
    @endif

    @if($actionType === 'withdraw' || $actionType === 'inject')
        <form wire:submit="submitTransfer" class="bg-white dark:bg-zinc-800 p-6 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 space-y-5 animate-fade-in-up">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2 {{ $actionType === 'withdraw' ? 'bg-green-100 text-green-600' : 'bg-purple-100 text-purple-600' }}">
                @if($actionType === 'withdraw')
                    <x-heroicon-o-arrow-down-tray class="w-6 h-6" />
                @else
                    <x-heroicon-o-arrow-up-tray class="w-6 h-6" />
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Toko / Bisnis Terkait</label>
                <select wire:model.live="businessId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-green-500 font-semibold text-sm appearance-none">
                    @forelse($userBusinesses as $biz)
                        <option value="{{ $biz->id }}">{{ $biz->name }}</option>
                    @empty
                        <option value="">Belum ditugaskan ke bisnis apapun</option>
                    @endforelse
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 relative mt-2">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="bg-zinc-100 dark:bg-zinc-900 p-1.5 rounded-full text-zinc-500 dark:text-zinc-400 z-10 border-2 border-white dark:border-zinc-800">
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Dari Dompet</label>
                    <select wire:model="fromAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-xs">
                        <option value="">-- Pilih --</option>
                        @if($actionType === 'withdraw')
                            @foreach($businessAccounts as $acc) 
                            <option value="{{ $acc->id }}">{{ $acc->name }} - {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                            @endforeach
                        @else
                            @foreach($personalAccounts as $acc) 
                            <option value="{{ $acc->id }}">{{ $acc->name }} - {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1.5 text-right">Ke Dompet</label>
                    <select wire:model="toAccountId" required class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-3 focus:ring-2 focus:ring-green-500 text-xs">
                        <option value="">-- Pilih --</option>
                        @if($actionType === 'withdraw')
                            @foreach($personalAccounts as $acc) 
                                <option value="{{ $acc->id }}">{{ $acc->name }} - {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                            @endforeach
                        @else
                            @foreach($businessAccounts as $acc) 
                                <option value="{{ $acc->id }}">{{ $acc->name }} - {{ number_format($acc->current_balance, 0, ',', '.') }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Nominal (Rp)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-zinc-400">Rp</span>
                    <input type="number" wire:model="amount" required min="1" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-4 pl-12 focus:ring-2 focus:ring-green-500 font-extrabold text-xl shadow-inner" placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1.5">Catatan</label>
                <input type="text" wire:model="notes" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-green-500 text-sm" placeholder="Opsional...">
            </div>

            <button type="submit" class="w-full py-4 mt-2 {{ $actionType === 'withdraw' ? 'bg-green-600 hover:bg-green-700' : 'bg-purple-600 hover:bg-purple-700' }} text-white rounded-xl font-bold text-sm transition-colors flex justify-center items-center gap-2 shadow-md">
                <x-heroicon-o-paper-airplane class="w-5 h-5 -mt-0.5" /> Eksekusi Transfer
            </button>
        </form>
    @endif

</div>
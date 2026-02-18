<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Business;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pwa')] class extends Component {
    
    public string $analyticMode = 'personal'; 
    public ?int $selectedBusinessId = null;

    // STATE: MODAL RINCIAN
    public bool $showDetailModal = false;
    public string $detailModalTitle = '';
    public string $detailModalColor = '';
    public $detailTransactions = [];

    public function mount()
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];
        
        $firstBusiness = Business::whereHas('users', function($q) use ($familyIds) {
            $q->whereIn('users.id', $familyIds);
        })->first();

        if ($firstBusiness) {
            $this->selectedBusinessId = $firstBusiness->id;
        }
    }

    public function setMode($mode)
    {
        $this->analyticMode = $mode;
    }

    // Fungsi untuk membuka Modal Rincian
    public function openDetailModal($type)
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];
        $thisMonth = Carbon::now()->startOfMonth();

        $query = Transaction::whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->where('date', '>=', $thisMonth)
            ->with(['category', 'account'])
            ->orderBy('amount', 'desc');

        if ($type === 'productive_saving') {
            $this->detailModalTitle = 'Rincian Investasi / Tabungan (Produktif)';
            $this->detailModalColor = 'text-green-600 dark:text-green-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'productive')->where('nature', 'saving'))->get();
        } elseif ($type === 'productive_need') {
            $this->detailModalTitle = 'Rincian SDM / Pendidikan (Produktif)';
            $this->detailModalColor = 'text-blue-600 dark:text-blue-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'productive')->where('nature', 'need'))->get();
        } elseif ($type === 'neutral') {
            $this->detailModalTitle = 'Rincian Kewajiban / Penahan Nilai (Netral)';
            $this->detailModalColor = 'text-amber-600 dark:text-amber-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'neutral'))->get();
        } elseif ($type === 'consumptive') {
            $this->detailModalTitle = 'Rincian Pengeluaran Hangus (Konsumtif)';
            $this->detailModalColor = 'text-red-600 dark:text-red-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'consumptive'))->get();
        }

        $this->showDetailModal = true;
    }

    public function with(): array
    {
        $user = Auth::user();
        $thisMonth = Carbon::now()->startOfMonth();
        $familyIds = $user->family_ids ?? [$user->id]; 

        // ==========================================
        // DATA ANALYTICS PERSONAL (KELUARGA)
        // ==========================================
        $personalExpenseQuery = Transaction::whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Transfer Keluar',
                ]));

        $totalPersonalExpense = (clone $personalExpenseQuery)->sum('amount');

        // Variabel Dimensi 1 (Nature)
        $needsTotal = 0; $wantsTotal = 0; $savingsTotal = 0;
        
        // Variabel Dimensi 2 (Productivity)
        $productiveTotal = 0; $consumptiveTotal = 0; $neutralTotal = 0;

        // Variabel Pecahan Detail (Untuk Rincian Card)
        $prodSavingTotal = 0; $prodNeedTotal = 0;
        
        $expenses = (clone $personalExpenseQuery)->with('category')->get();
        
        foreach ($expenses as $ex) {
            $cat = $ex->category;
            
            // 1. Hitung Berdasarkan Prioritas (Nature)
            if ($cat->nature === 'need') $needsTotal += $ex->amount;
            elseif ($cat->nature === 'want') $wantsTotal += $ex->amount;
            elseif ($cat->nature === 'saving') $savingsTotal += $ex->amount;
            else $needsTotal += $ex->amount; // Fallback

            // 2. Hitung Berdasarkan Kualitas (Productivity) & Rincian
            if ($cat->productivity === 'productive') {
                $productiveTotal += $ex->amount;
                // Pecah produktif
                if ($cat->nature === 'saving') $prodSavingTotal += $ex->amount;
                else $prodNeedTotal += $ex->amount;
            } 
            elseif ($cat->productivity === 'consumptive') {
                $consumptiveTotal += $ex->amount;
            } 
            elseif ($cat->productivity === 'neutral') {
                $neutralTotal += $ex->amount;
            } 
            else {
                $consumptiveTotal += $ex->amount; // Fallback
            }
        }

        // Persentase Prioritas (Nature)
        $needsPct = $totalPersonalExpense > 0 ? round(($needsTotal / $totalPersonalExpense) * 100) : 0;
        $wantsPct = $totalPersonalExpense > 0 ? round(($wantsTotal / $totalPersonalExpense) * 100) : 0;
        $savingsPct = $totalPersonalExpense > 0 ? round(($savingsTotal / $totalPersonalExpense) * 100) : 0;

        // Persentase Kualitas (Productivity)
        $prodPct = $totalPersonalExpense > 0 ? round(($productiveTotal / $totalPersonalExpense) * 100) : 0;
        $consPct = $totalPersonalExpense > 0 ? round(($consumptiveTotal / $totalPersonalExpense) * 100) : 0;
        $neutPct = $totalPersonalExpense > 0 ? round(($neutralTotal / $totalPersonalExpense) * 100) : 0;

        // Ambil Top 5 Kategori Konsumtif Terbesar
        $topConsumptiveCategories = Transaction::select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->where('date', '>=', $thisMonth)
            ->whereHas('category', fn($q) => $q->where('type', 'expense')->where('productivity', 'consumptive'))
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->with('category')
            ->get();

        // ==========================================
        // DATA ANALYTICS BISNIS
        // ==========================================
        $familyBusinesses = Business::whereHas('users', function($q) use ($familyIds) {
            $q->whereIn('users.id', $familyIds);
        })->get();

        $selectedBizData = [
            'modalAwal' => 0, 'sales' => 0, 'hpp' => 0, 'opEx' => 0, 'totalExpense' => 0, 
            'profit' => 0, 'withdraw' => 0, 'sisaProfit' => 0, 'piutang' => 0, 'hutang' => 0, 
            'kas' => 0, 'healthStatus' => 'sehat', 'healthMessage' => ''
        ];

        if ($this->selectedBusinessId) {
            $saldoAwal = Account::where('business_id', $this->selectedBusinessId)->sum('opening_balance');
            $suntikModalAllTime = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereHas('category', fn($q) => $q->where('name', 'Suntikan Modal Tambahan'))->sum('amount');
            $modalAwal = $saldoAwal + $suntikModalAllTime;

            $sales = Order::where('business_id', $this->selectedBusinessId)->where('order_date', '>=', $thisMonth)->sum('total_amount');
            $hpp = Purchase::where('business_id', $this->selectedBusinessId)->where('date', '>=', $thisMonth)->sum('total_amount');
            $opEx = Transaction::where('business_id', $this->selectedBusinessId)->where('date', '>=', $thisMonth)
                ->whereHas('category', fn($q) => $q->where('type', 'expense')
                    ->whereNotIn('name', ['Bahan Baku / Pembelian Stok', 'Penarikan Prive / Deviden', 'Transfer Keluar']))
                ->sum('amount');
            $totalExpense = $hpp + $opEx;
            $profit = $sales - $totalExpense;

            $withdraw = Transaction::where('business_id', $this->selectedBusinessId)->where('date', '>=', $thisMonth)
                ->whereHas('category', fn($q) => $q->where('name', 'Penarikan Prive / Deviden'))->sum('amount');
            $sisaProfit = $profit - $withdraw;

            $piutang = Order::where('business_id', $this->selectedBusinessId)->where('payment_status', 'unpaid')->sum('total_amount');
            $hutang = Purchase::where('business_id', $this->selectedBusinessId)->where('payment_status', 'unpaid')->sum('total_amount');

            $bizTotalIncome = Transaction::where('business_id', $this->selectedBusinessId)->whereHas('category', fn($q) => $q->where('type', 'income')
                ->whereNotIn('name', [
                    'Transfer Masuk',
                ]))->sum('amount');
            $bizTotalExpense = Transaction::where('business_id', $this->selectedBusinessId)->whereHas('category', fn($q) => $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Transfer Keluar',
                ]))->sum('amount');
            $kasBisnis = $saldoAwal + $bizTotalIncome - $bizTotalExpense;

            $healthStatus = 'sehat';
            $healthMessage = 'Keuangan bisnis sangat sehat! Kas aman, profit positif, dan penarikan wajar.';

            if ($profit < 0) {
                $healthStatus = 'sakit';
                $healthMessage = 'Bisnis merugi bulan ini! Evaluasi harga jual atau pangkas biaya operasional segera.';
            } else {
                if ($sisaProfit < 0) {
                    $healthStatus = 'waspada';
                    $healthMessage = 'Profit positif, TAPI penarikan deviden terlalu besar (overlimit). Awas modal utamamu tergerus!';
                } elseif ($kasBisnis < $hutang) {
                    $healthStatus = 'waspada';
                    $healthMessage = 'Profit positif, TAPI uang kas fisik tidak cukup untuk membayar tagihan hutang supplier (Krisis Likuiditas). Segera tagih piutang pelanggan!';
                }
            }

            $selectedBizData = [
                'modalAwal' => $modalAwal, 'sales' => $sales, 'hpp' => $hpp, 'opEx' => $opEx, 
                'totalExpense' => $totalExpense, 'profit' => $profit, 'withdraw' => $withdraw, 
                'sisaProfit' => $sisaProfit, 'piutang' => $piutang, 'hutang' => $hutang, 
                'kas' => $kasBisnis, 'healthStatus' => $healthStatus, 'healthMessage' => $healthMessage
            ];
        }

        return [
            'userBusinesses' => $familyBusinesses,
            'totalPersonalExpense' => $totalPersonalExpense,
            
            // Variabel Progress Bar Tetap Ada!
            'needsTotal' => $needsTotal, 'wantsTotal' => $wantsTotal, 'savingsTotal' => $savingsTotal,
            'needsPct' => $needsPct, 'wantsPct' => $wantsPct, 'savingsPct' => $savingsPct,
            'productiveTotal' => $productiveTotal, 'consumptiveTotal' => $consumptiveTotal, 'neutralTotal' => $neutralTotal,
            'prodPct' => $prodPct, 'consPct' => $consPct, 'neutPct' => $neutPct,

            // Variabel Pecahan Rincian
            'prodSavingTotal' => $prodSavingTotal,
            'prodNeedTotal' => $prodNeedTotal,
            'topConsumptiveCategories' => $topConsumptiveCategories,
            
            'bizData' => $selectedBizData,
        ];
    }
};
?>

<div class="animate-fade-in space-y-6">
    
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-teal-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-green-500/20">
                <x-heroicon-s-chart-pie class="w-6 h-6" />
            </div>
            <div>
                <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Analytics</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Evaluasi kesehatan finansialmu.</p>
            </div>
        </div>

        <div class="bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl flex w-full border border-zinc-200 dark:border-zinc-700 shadow-sm mt-2 relative">
            <button wire:click="setMode('personal')" class="flex-1 py-2 text-sm font-bold rounded-xl transition-all {{ $analyticMode === 'personal' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
                Rumah Tangga
            </button>
            <button wire:click="setMode('business')" class="flex-1 py-2 text-sm font-bold rounded-xl transition-all {{ $analyticMode === 'business' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
                Performa Bisnis
            </button>
        </div>
    </div>

    @if($analyticMode === 'personal')
        <div class="animate-fade-in space-y-5 pb-10">
            
            <div class="bg-zinc-800 dark:bg-zinc-900 rounded-3xl p-6 text-white shadow-md text-center relative overflow-hidden border border-zinc-700 dark:border-zinc-800">
                <div class="absolute -top-6 -right-6 w-24 h-24 bg-green-500 opacity-20 rounded-full blur-2xl"></div>
                <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1 relative z-10">Total Uang Keluar</p>
                <h3 class="text-4xl font-extrabold text-green-400 relative z-10 tracking-tight">
                    Rp {{ number_format($totalPersonalExpense, 0, ',', '.') }}
                </h3>
            </div>

            <div class="space-y-4">
                <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                            <x-heroicon-o-scale class="w-5 h-5 text-indigo-500" /> Alokasi Prioritas
                        </h4>
                        <span class="text-[10px] font-bold text-zinc-400 uppercase">Nature</span>
                    </div>
                    
                    <div class="w-full h-4 bg-zinc-100 dark:bg-zinc-700 rounded-full flex overflow-hidden mb-5 shadow-inner">
                        <div style="width: {{ $needsPct }}%" class="h-full bg-blue-500 transition-all duration-1000"></div>
                        <div style="width: {{ $wantsPct }}%" class="h-full bg-pink-500 transition-all duration-1000 border-l border-white/20"></div>
                        <div style="width: {{ $savingsPct }}%" class="h-full bg-emerald-500 transition-all duration-1000 border-l border-white/20"></div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div> Pokok</span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($needsTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $needsPct }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-pink-600 dark:text-pink-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-pink-500"></div> Gaya Hidup</span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($wantsTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $wantsPct }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div> Tabungan/Aset</span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($savingsTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $savingsPct }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                            <x-heroicon-o-sparkles class="w-5 h-5 text-amber-500" /> Kualitas Pengeluaran
                        </h4>
                        <span class="text-[10px] font-bold text-zinc-400 uppercase">Productivity</span>
                    </div>
                    
                    <div class="w-full h-4 bg-zinc-100 dark:bg-zinc-700 rounded-full flex overflow-hidden mb-5 shadow-inner">
                        <div style="width: {{ $prodPct }}%" class="h-full bg-green-500 transition-all duration-1000"></div>
                        <div style="width: {{ $neutPct }}%" class="h-full bg-amber-500 transition-all duration-1000 border-l border-white/20"></div>
                        <div style="width: {{ $consPct }}%" class="h-full bg-red-500 transition-all duration-1000 border-l border-white/20"></div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-green-600 dark:text-green-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-green-500"></div> Produktif <span class="text-[9px] font-normal text-zinc-400">(Aset)</span></span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($productiveTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $prodPct }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div> Netral <span class="text-[9px] font-normal text-zinc-400">(Kewajiban)</span></span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($neutralTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $neutPct }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-red-600 dark:text-red-400 flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div> Konsumtif <span class="text-[9px] font-normal text-zinc-400">(Hangus)</span></span>
                            <div class="text-right">
                                <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($consumptiveTotal, 0, ',', '.') }}</span>
                                <span class="text-[10px] text-zinc-400 ml-1 font-medium">({{ $consPct }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <h3 class="text-sm font-extrabold text-zinc-800 dark:text-zinc-100 mb-3 px-1">Rincian & Evaluasi</h3>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div wire:click="openDetailModal('productive_saving')" class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 cursor-pointer hover:border-green-500 transition group relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-12 h-12 bg-green-50 dark:bg-green-900/20 rounded-full group-hover:scale-110 transition-transform"></div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1 relative z-10">Nabung / Invest</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400 relative z-10">Rp {{ number_format($prodSavingTotal, 0, ',', '.') }}</p>
                        <p class="text-[9px] text-zinc-400 mt-1 relative z-10 flex items-center gap-1">Ketuk rincian <x-heroicon-o-arrow-right class="w-2.5 h-2.5" /></p>
                    </div>
                    <div wire:click="openDetailModal('productive_need')" class="bg-white dark:bg-zinc-800 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 cursor-pointer hover:border-blue-500 transition group relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-full group-hover:scale-110 transition-transform"></div>
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1 relative z-10">Pendidikan / SDM</p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400 relative z-10">Rp {{ number_format($prodNeedTotal, 0, ',', '.') }}</p>
                        <p class="text-[9px] text-zinc-400 mt-1 relative z-10 flex items-center gap-1">Ketuk rincian <x-heroicon-o-arrow-right class="w-2.5 h-2.5" /></p>
                    </div>
                </div>

                <div wire:click="openDetailModal('neutral')" class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 cursor-pointer hover:border-amber-500 transition group flex justify-between items-center relative overflow-hidden mb-4">
                    <div class="absolute right-0 top-0 w-24 h-full bg-amber-50 dark:bg-amber-900/20 rounded-l-full blur-xl group-hover:bg-amber-100 dark:group-hover:bg-amber-900/40 transition-colors"></div>
                    <div class="relative z-10">
                        <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <x-heroicon-o-scale class="w-4 h-4 text-amber-500" /> Kewajiban (Netral)
                        </h3>
                        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-500">Rp {{ number_format($neutralTotal, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-zinc-400 mt-0.5">Co: bayar KPR, cicilan, listrik.</p>
                    </div>
                    <x-heroicon-o-chevron-right class="w-5 h-5 text-zinc-300 group-hover:text-amber-500 relative z-10" />
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div>
                            <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <x-heroicon-o-fire class="w-4 h-4 text-red-500" /> Uang Hangus (Konsumtif)
                            </h3>
                            <p class="text-2xl font-extrabold text-red-600 dark:text-red-500">Rp {{ number_format($consumptiveTotal, 0, ',', '.') }}</p>
                        </div>
                        <button wire:click="openDetailModal('consumptive')" class="text-[10px] bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-3 py-1.5 rounded-lg font-bold hover:bg-red-100 transition">
                            Lihat Rincian
                        </button>
                    </div>

                    <div class="space-y-3 relative z-10 border-t border-dashed border-zinc-200 dark:border-zinc-700 pt-4">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Top 5 Kebocoran Bulan Ini:</p>
                        @forelse($topConsumptiveCategories as $top)
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2 w-2/3">
                                    <div class="w-2 h-2 rounded-full bg-red-400 shrink-0"></div>
                                    <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 truncate">{{ $top->category->name }}</p>
                                </div>
                                <p class="text-xs font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($top->total_amount, 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400 italic">Belum ada pengeluaran konsumtif.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if($consPct > 50)
                <div class="p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-2xl flex gap-3 shadow-sm mt-4">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 shrink-0" />
                    <p class="text-xs text-red-700 dark:text-red-400 font-medium leading-relaxed">Lebih dari separuh uangmu ({{ $consPct }}%) hangus untuk kebutuhan konsumtif! Cek Top 5 di atas dan kurangi jajan.</p>
                </div>
            @endif

        </div>
    @endif

    @if($analyticMode === 'business')
        <div class="animate-fade-in space-y-5 pb-10">
            
            <div class="relative mt-2">
                <select wire:model.live="selectedBusinessId" class="w-full appearance-none bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 py-3.5 px-4 pr-10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-bold text-sm">
                    @forelse($userBusinesses as $biz)
                        <option value="{{ $biz->id }}">{{ $biz->name }}</option>
                    @empty
                        <option value="">Belum ada bisnis</option>
                    @endforelse
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                    <x-heroicon-o-chevron-down class="w-5 h-5" />
                </div>
            </div>

            @if($selectedBusinessId)
                @if($bizData['healthStatus'] === 'sehat')
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4 rounded-3xl flex items-start gap-3 shadow-sm">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-800/50 text-emerald-600 dark:text-emerald-400 rounded-full shrink-0">
                            <x-heroicon-o-check-badge class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Status: Sehat Bugar 🚀</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400/80 mt-0.5 leading-relaxed">{{ $bizData['healthMessage'] }}</p>
                        </div>
                    </div>
                @elseif($bizData['healthStatus'] === 'waspada')
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 rounded-3xl flex items-start gap-3 shadow-sm">
                        <div class="p-2 bg-amber-100 dark:bg-amber-800/50 text-amber-600 dark:text-amber-400 rounded-full shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Status: Waspada Likuiditas ⚠️</p>
                            <p class="text-xs text-amber-600 dark:text-amber-400/80 mt-0.5 leading-relaxed">{{ $bizData['healthMessage'] }}</p>
                        </div>
                    </div>
                @else
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 rounded-3xl flex items-start gap-3 shadow-sm">
                        <div class="p-2 bg-red-100 dark:bg-red-800/50 text-red-600 dark:text-red-400 rounded-full shrink-0">
                            <x-heroicon-o-fire class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-red-800 dark:text-red-300">Status: Kritis / Rugi 🚨</p>
                            <p class="text-xs text-red-600 dark:text-red-400/80 mt-0.5 leading-relaxed">{{ $bizData['healthMessage'] }}</p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 p-4 rounded-3xl shadow-md text-white border border-zinc-700">
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider font-bold mb-1">Kas di Bank/Laci</p>
                        <p class="text-xl font-extrabold text-green-400">Rp {{ number_format($bizData['kas'], 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Total Modal Disuntik</p>
                        <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">Rp {{ number_format($bizData['modalAwal'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                    <div class="p-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                        <h4 class="text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider flex items-center gap-2">
                            <x-heroicon-o-calculator class="w-4 h-4 text-green-500" /> Ringkasan Laba Rugi (Bulan Ini)
                        </h4>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">1. Total Penjualan</p>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400">+ Rp {{ number_format($bizData['sales'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">2. Beli Stok / HPP</p>
                            <p class="text-sm font-bold text-red-500">- Rp {{ number_format($bizData['hpp'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">3. Biaya Operasional</p>
                            <p class="text-sm font-bold text-red-500">- Rp {{ number_format($bizData['opEx'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <p class="text-sm font-extrabold text-zinc-800 dark:text-zinc-100 uppercase">4. Estimasi Profit</p>
                            <p class="text-lg font-extrabold {{ $bizData['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600' }}">
                                Rp {{ number_format($bizData['profit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                    <div class="p-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                        <h4 class="text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider flex items-center gap-2">
                            <x-heroicon-o-banknotes class="w-4 h-4 text-purple-500" /> Arus Deviden / Prive
                        </h4>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Total Ditarik Owner</p>
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">- Rp {{ number_format($bizData['withdraw'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Sisa Laba Ditahan (Utk Bisnis)</p>
                            <p class="text-sm font-extrabold {{ $bizData['sisaProfit'] >= 0 ? 'text-zinc-800 dark:text-zinc-100' : 'text-red-600' }}">
                                Rp {{ number_format($bizData['sisaProfit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 border-t-4 border-t-amber-500">
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Piutang Pelanggan</p>
                        <p class="text-lg font-bold text-amber-600 dark:text-amber-500">Rp {{ number_format($bizData['piutang'], 0, ',', '.') }}</p>
                        <p class="text-[9px] text-zinc-400 mt-1">Uang nyangkut di luar</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 border-t-4 border-t-red-500">
                        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Hutang Supplier</p>
                        <p class="text-lg font-bold text-red-600 dark:text-red-500">Rp {{ number_format($bizData['hutang'], 0, ',', '.') }}</p>
                        <p class="text-[9px] text-zinc-400 mt-1">Tagihan wajib dibayar</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($showDetailModal)
        <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-zinc-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-t-3xl sm:rounded-3xl w-full max-w-md shadow-2xl border border-zinc-100 dark:border-zinc-700 animate-fade-in-up max-h-[85vh] flex flex-col">
                <div class="p-5 border-b border-zinc-100 dark:border-zinc-700 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50 rounded-t-3xl">
                    <h3 class="font-bold text-sm {{ $detailModalColor }}">{{ $detailModalTitle }}</h3>
                    <button wire:click="$set('showDetailModal', false)" class="p-2 bg-zinc-200 dark:bg-zinc-700 rounded-full text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300 transition">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>
                
                <div class="p-5 overflow-y-auto space-y-4 bg-white dark:bg-zinc-800">
                    @forelse($detailTransactions as $trx)
                        <div class="flex justify-between items-center border-b border-zinc-100 dark:border-zinc-700/50 pb-3 last:border-0 last:pb-0">
                            <div class="w-2/3 pr-2">
                                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 truncate">{{ $trx->category->name }}</p>
                                <p class="text-[10px] text-zinc-500 truncate">{{ \Carbon\Carbon::parse($trx->date)->format('d M') }} • {{ $trx->description ?: 'Tanpa catatan' }}</p>
                            </div>
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 whitespace-nowrap">
                                Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-heroicon-o-document-magnifying-glass class="w-10 h-10 mx-auto text-zinc-300 mb-2" />
                            <p class="text-sm text-zinc-500 font-medium">Belum ada transaksi di kategori ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

</div>
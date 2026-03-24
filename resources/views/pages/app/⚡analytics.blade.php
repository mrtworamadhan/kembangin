
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;
use App\Models\Business;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::pwa')] class extends Component {
    
    public string $analyticMode = 'personal'; 
    public ?int $selectedBusinessId = null;

    // STATE: FILTER TANGGAL
    public int $selectedMonth;
    public int $selectedYear;

    public string $trendPeriod = 'monthly';

    // STATE: MODAL RINCIAN
    public bool $showDetailModal = false;
    public string $detailModalTitle = '';
    public string $detailModalColor = '';
    public $detailTransactions = [];

    public function mount()
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];

        // Default ke bulan & tahun sekarang
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear = (int) now()->year;
        
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

    // Helper untuk mendapatkan range tanggal yang dipilih
    private function getSelectedRange()
    {
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        return [$start, $end];
    }

    public function openDetailModal($type)
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];
        [$start, $end] = $this->getSelectedRange();

        $query = Transaction::whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->whereBetween('date', [$start, $end])
            ->with(['category', 'account'])
            ->orderBy('amount', 'desc');

        if ($type === 'productive_saving') {
            $this->detailModalTitle = 'Rincian Investasi / Tabungan';
            $this->detailModalColor = 'text-green-600 dark:text-green-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'productive')->where('nature', 'saving'))->get();
        } elseif ($type === 'productive_need') {
            $this->detailModalTitle = 'Rincian SDM / Pendidikan';
            $this->detailModalColor = 'text-blue-600 dark:text-blue-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'productive')->where('nature', 'need'))->get();
        } elseif ($type === 'neutral') {
            $this->detailModalTitle = 'Rincian Kewajiban (Netral)';
            $this->detailModalColor = 'text-amber-600 dark:text-amber-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'neutral'))->get();
        } elseif ($type === 'consumptive') {
            $this->detailModalTitle = 'Rincian Pengeluaran Hangus';
            $this->detailModalColor = 'text-red-600 dark:text-red-400';
            $this->detailTransactions = (clone $query)->whereHas('category', fn($q) => $q->where('productivity', 'consumptive'))->get();
        }

        $this->showDetailModal = true;
    }

    private function getTrendData($familyIds)
    {
        $labels = [];
        $series = [];

        $now = Carbon::now();
        $periods = [];

        if ($this->trendPeriod === 'monthly') {
            for ($i = 5; $i >= 0; $i--) {
                $start = $now->copy()->subMonths($i)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $periods[] = ['label' => $start->translatedFormat('M Y'), 'start' => $start, 'end' => $end];
            }
        } elseif ($this->trendPeriod === 'quarterly') {
            for ($i = 3; $i >= 0; $i--) {
                $start = $now->copy()->subQuarters($i)->firstOfQuarter();
                $end = $start->copy()->lastOfQuarter();
                $periods[] = ['label' => 'Q'.$start->quarter.' '.$start->year, 'start' => $start, 'end' => $end];
            }
        } elseif ($this->trendPeriod === 'semester') {
            for ($i = 3; $i >= 0; $i--) {
                $target = $now->copy()->subMonths($i * 6);
                $semester = $target->month <= 6 ? 1 : 2;
                $start = Carbon::create($target->year, $semester == 1 ? 1 : 7, 1)->startOfMonth();
                $end = Carbon::create($target->year, $semester == 1 ? 6 : 12, 1)->endOfMonth();
                $periods[] = ['label' => 'Smt '.$semester.' '.$start->year, 'start' => $start, 'end' => $end];
            }
        } elseif ($this->trendPeriod === 'yearly') {
            for ($i = 4; $i >= 0; $i--) {
                $start = $now->copy()->subYears($i)->startOfYear();
                $end = $start->copy()->endOfYear();
                $periods[] = ['label' => $start->year, 'start' => $start, 'end' => $end];
            }
        }

        foreach ($periods as $p) {
            $labels[] = $p['label'];
        }

        if ($this->analyticMode === 'personal') {
            $dataPokok = []; $dataGayaHidup = []; $dataTabungan = [];

            foreach ($periods as $p) {
                $expenses = Transaction::whereNull('business_id')->whereIn('user_id', $familyIds)
                    ->whereBetween('date', [$p['start'], $p['end']])
                    ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Transfer Keluar']))
                    ->with('category')->get();

                $pokok = 0; $gayaHidup = 0; $tabungan = 0;
                foreach($expenses as $ex) {
                    if ($ex->category->nature === 'need') $pokok += $ex->amount;
                    elseif ($ex->category->nature === 'want') $gayaHidup += $ex->amount;
                    elseif ($ex->category->nature === 'saving') $tabungan += $ex->amount;
                }
                
                $dataPokok[] = $pokok;
                $dataGayaHidup[] = $gayaHidup;
                $dataTabungan[] = $tabungan;
            }

            $series = [
                ['name' => 'Kebutuhan Pokok', 'data' => $dataPokok],
                ['name' => 'Gaya Hidup', 'data' => $dataGayaHidup],
                ['name' => 'Tabungan / Aset', 'data' => $dataTabungan],
            ];

        } else {
            $dataOmzet = []; $dataHpp = []; $dataOpex = []; $dataProfit = [];

            if ($this->selectedBusinessId) {
                foreach ($periods as $p) {
                    $sales = Order::where('business_id', $this->selectedBusinessId)->whereBetween('order_date', [$p['start'], $p['end']])->sum('total_amount');
                    $hpp = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.business_id', $this->selectedBusinessId)->whereBetween('orders.order_date', [$p['start'], $p['end']])->sum('order_items.total_base_price');
                    $opex = Transaction::where('business_id', $this->selectedBusinessId)->whereBetween('date', [$p['start'], $p['end']])
                        ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Bahan Baku / Pembelian Stok', 'Penarikan Prive / Deviden', 'Transfer Keluar']))->sum('amount');
                    $profit = $sales - $hpp - $opex;

                    $dataOmzet[] = $sales;
                    $dataHpp[] = $hpp;
                    $dataOpex[] = $opex;
                    $dataProfit[] = $profit;
                }
            }

            $series = [
                ['name' => 'Omzet (Sales)', 'data' => $dataOmzet],
                ['name' => 'Profit Bersih', 'data' => $dataProfit],
                ['name' => 'HPP (Modal)', 'data' => $dataHpp],
                ['name' => 'Operasional', 'data' => $dataOpex],
            ];
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function with(): array
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id]; 
        [$start, $end] = $this->getSelectedRange();

        // ==========================================
        // DATA ANALYTICS PERSONAL (KELUARGA)
        // ==========================================
        $personalExpenseQuery = Transaction::whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->whereBetween('date', [$start, $end])
            ->whereHas('category', fn($q) => $q->where('type', 'expense')
                ->whereNotIn('name', ['Transfer Keluar']));

        $totalPersonalExpense = (clone $personalExpenseQuery)->sum('amount');

        $needsTotal = 0; $wantsTotal = 0; $savingsTotal = 0;
        $productiveTotal = 0; $consumptiveTotal = 0; $neutralTotal = 0;
        $prodSavingTotal = 0; $prodNeedTotal = 0;
        
        $expenses = (clone $personalExpenseQuery)->with('category')->get();
        
        foreach ($expenses as $ex) {
            $cat = $ex->category;
            if ($cat->nature === 'need') $needsTotal += $ex->amount;
            elseif ($cat->nature === 'want') $wantsTotal += $ex->amount;
            elseif ($cat->nature === 'saving') $savingsTotal += $ex->amount;

            if ($cat->productivity === 'productive') {
                $productiveTotal += $ex->amount;
                if ($cat->nature === 'saving') $prodSavingTotal += $ex->amount;
                else $prodNeedTotal += $ex->amount;
            } elseif ($cat->productivity === 'consumptive') {
                $consumptiveTotal += $ex->amount;
            } elseif ($cat->productivity === 'neutral') {
                $neutralTotal += $ex->amount;
            }
        }

        $needsPct = $totalPersonalExpense > 0 ? round(($needsTotal / $totalPersonalExpense) * 100) : 0;
        $wantsPct = $totalPersonalExpense > 0 ? round(($wantsTotal / $totalPersonalExpense) * 100) : 0;
        $savingsPct = $totalPersonalExpense > 0 ? round(($savingsTotal / $totalPersonalExpense) * 100) : 0;
        $prodPct = $totalPersonalExpense > 0 ? round(($productiveTotal / $totalPersonalExpense) * 100) : 0;
        $consPct = $totalPersonalExpense > 0 ? round(($consumptiveTotal / $totalPersonalExpense) * 100) : 0;
        $neutPct = $totalPersonalExpense > 0 ? round(($neutralTotal / $totalPersonalExpense) * 100) : 0;

        $topConsumptiveCategories = Transaction::select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->whereNull('business_id')
            ->whereIn('user_id', $familyIds)
            ->whereBetween('date', [$start, $end])
            ->whereHas('category', fn($q) => $q->where('type', 'expense')->where('productivity', 'consumptive'))
            ->groupBy('category_id')->orderByDesc('total_amount')->limit(5)->with('category')->get();

        // ==========================================
        // DATA ANALYTICS BISNIS
        // ==========================================
        $selectedBizData = [
            'modalAwal' => 0, 'sales' => 0, 'hpp' => 0, 'opEx' => 0, 'totalExpense' => 0, 
            'profit' => 0, 'withdraw' => 0,'total_profit' => 0, 'sisaProfit' => 0, 'piutang' => 0, 'hutang' => 0, 
            'kas' => 0, 'healthStatus' => 'sehat', 'healthMessage' => ''
        ];

        if ($this->selectedBusinessId) {
            // A. Saldo Awal & Modal (All Time)
            $saldoAwal = Account::where('business_id', $this->selectedBusinessId)->sum('opening_balance');
            $suntikModalAllTime = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereHas('category', fn($q) => $q->where('name', 'Suntikan Modal Tambahan'))->sum('amount');
            $modalAwal = $saldoAwal + $suntikModalAllTime;

            // B. Performa Bulan Terpilih (Accrual Basis)
            $sales = Order::where('business_id', $this->selectedBusinessId)->whereBetween('order_date', [$start, $end])->sum('total_amount');
            // HPP: Hitung dari Snapshot Order Items
            $hpp = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.business_id', $this->selectedBusinessId)
                ->whereBetween('orders.order_date', [$start, $end])
                ->sum('order_items.total_base_price');

            $opEx = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereBetween('date', [$start, $end])
                ->whereHas('category', fn($q) => $q->where('type', 'expense')
                    ->whereNotIn('name', ['Bahan Baku / Pembelian Stok', 'Penarikan Prive / Deviden', 'Transfer Keluar']))
                ->sum('amount');
            
            $profit = $sales - $hpp - $opEx;

            // C. Laba Ditahan (Akumulatif s/d akhir bulan terpilih)
            $totalSalesAllTime = Order::where('business_id', $this->selectedBusinessId)->where('order_date', '<=', $end)->sum('total_amount');
            $totalHppAllTime = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.business_id', $this->selectedBusinessId)
                ->where('orders.order_date', '<=', $end)
                ->sum('order_items.total_base_price');
            $totalOpExAllTime = Transaction::where('business_id', $this->selectedBusinessId)->where('date', '<=', $end)
                ->whereHas('category', fn($q) => $q->where('type', 'expense')
                    ->whereNotIn('name', ['Bahan Baku / Pembelian Stok', 'Penarikan Prive / Deviden', 'Transfer Keluar']))
                ->sum('amount');

            $withdrawAllTime = Transaction::where('business_id', $this->selectedBusinessId)->where('date', '<=', $end)
                ->whereHas('category', fn($q) => $q->where('name', 'Penarikan Prive / Deviden'))->sum('amount');

            $totalProfit = $totalSalesAllTime - ($totalHppAllTime + $totalOpExAllTime);    
            $sisaProfit = ($totalSalesAllTime - ($totalHppAllTime + $totalOpExAllTime)) - $withdrawAllTime;

            // D. Kas & Likuiditas (Real-time saat ini)
            $bizIncome = Transaction::where('business_id', $this->selectedBusinessId)->whereHas('category', fn($q) => $q->where('type', 'income')->whereNotIn('name', ['Transfer Masuk', 'Suntikan Modal Tambahan']))->sum('amount');
            $bizExpense = Transaction::where('business_id', $this->selectedBusinessId)->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Transfer Keluar']))->sum('amount');
            $kasBisnis = $modalAwal + $bizIncome - $bizExpense;
            
            $piutang = Order::where('business_id', $this->selectedBusinessId)->where('payment_status', 'unpaid')->sum('total_amount');
            $hutang = Purchase::where('business_id', $this->selectedBusinessId)->where('payment_status', 'unpaid')->sum('total_amount');

            // E. Health Analysis
            $healthStatus = 'sehat';
            $healthMessage = 'Bisnis dalam kondisi prima.';
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
                'totalExpense' => $hpp + $opEx, 'profit' => $profit, 'withdraw' => $withdrawAllTime, 
                'sisaProfit' => $sisaProfit, 'total_profit' => $totalProfit, 'piutang' => $piutang, 'hutang' => $hutang, 
                'kas' => $kasBisnis, 'healthStatus' => $healthStatus, 'healthMessage' => $healthMessage
            ];
        }

        $chartData = $this->getTrendData($familyIds);

        return [
            'userBusinesses' => Business::whereHas('users', fn($q) => $q->whereIn('users.id', $familyIds))->get(),
            'totalPersonalExpense' => $totalPersonalExpense,
            'needsPct' => $needsPct, 'wantsPct' => $wantsPct, 'savingsPct' => $savingsPct,
            'prodPct' => $prodPct, 'consPct' => $consPct, 'neutPct' => $neutPct,
            'prodSavingTotal' => $prodSavingTotal, 'prodNeedTotal' => $prodNeedTotal,
            'topConsumptiveCategories' => $topConsumptiveCategories,
            'bizData' => $selectedBizData,
            'needsTotal' => $needsTotal, 'wantsTotal' => $wantsTotal, 'savingsTotal' => $savingsTotal,
            'productiveTotal' => $productiveTotal, 'consumptiveTotal' => $consumptiveTotal, 'neutralTotal' => $neutralTotal,
            'chartData' => $chartData,    
        ];
    }

    public function downloadReport()
    {
        $user = Auth::user();
        $familyIds = $user->household_id 
            ? User::where('household_id', $user->household_id)->pluck('id')->toArray() 
            : [$user->id];

        [$start, $end] = $this->getSelectedRange();
        $monthName = Carbon::create()->month($this->selectedMonth)->translatedFormat('F');
        $year = $this->selectedYear;

        if ($this->analyticMode === 'business' && $this->selectedBusinessId) {
            $business = Business::find($this->selectedBusinessId);
            
            $sales = Order::where('business_id', $this->selectedBusinessId)->whereBetween('order_date', [$start, $end])->sum('total_amount');
            $hpp = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.business_id', $this->selectedBusinessId)->whereBetween('orders.order_date', [$start, $end])->sum('order_items.total_base_price');
            $opEx = Transaction::where('business_id', $this->selectedBusinessId)->whereBetween('date', [$start, $end])
                ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Bahan Baku / Pembelian Stok', 'Penarikan Prive / Deviden', 'Transfer Keluar']))->sum('amount');
            
            $profit = $sales - $hpp - $opEx;
            $profitMargin = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;

            $orders = Order::where('business_id', $this->selectedBusinessId)
                ->whereBetween('order_date', [$start, $end])
                ->orderBy('order_date', 'asc')
                ->get();

            $expenses = Transaction::where('business_id', $this->selectedBusinessId)
                ->whereBetween('date', [$start, $end])
                ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Penarikan Prive / Deviden', 'Transfer Keluar']))
                ->with('category')
                ->orderBy('date', 'asc')
                ->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.business-monthly', compact(
                'business', 'monthName', 'year', 'sales', 'hpp', 'opEx', 'profit', 'profitMargin', 'orders', 'expenses'
            ))->setPaper('a4', 'portrait');

            return response()->streamDownload(fn() => print($pdf->output()), "Laporan-Bisnis-{$business->name}-{$monthName}-{$year}.pdf");
        
        } else {
            
            $expenses = Transaction::whereNull('business_id')
                ->whereIn('user_id', $familyIds)
                ->whereBetween('date', [$start, $end])
                ->whereHas('category', fn($q) => $q->where('type', 'expense')->whereNotIn('name', ['Transfer Keluar']))
                ->with(['category', 'user']) 
                ->orderBy('date', 'asc')
                ->get();

            $totalExpense = $expenses->sum('amount');
            $needs = $expenses->where('category.nature', 'need')->sum('amount');
            $wants = $expenses->where('category.nature', 'want')->sum('amount');
            $savings = $expenses->where('category.nature', 'saving')->sum('amount');
            $productive = $expenses->where('category.productivity', 'productive')->sum('amount');
            $consumptive = $expenses->where('category.productivity', 'consumptive')->sum('amount');
            $neutral = $expenses->where('category.productivity', 'neutral')->sum('amount');

            $incomes = Transaction::whereNull('business_id')
                ->whereIn('user_id', $familyIds)
                ->whereBetween('date', [$start, $end])
                ->whereHas('category', fn($q) => $q->where('type', 'income')->whereNotIn('name', ['Transfer Masuk']))
                ->with(['category', 'user']) 
                ->orderBy('date', 'asc')
                ->get();
                
            $totalIncome = $incomes->sum('amount');
            $netCashflow = $totalIncome - $totalExpense;

            $accounts = Account::whereNull('business_id')
                ->whereIn('user_id', $familyIds)
                ->with('user')
                ->get()->map(function($acc) {
                    $income = Transaction::where('account_id', $acc->id)
                        ->whereHas('category', fn($q) => $q->where('type', 'income'))->sum('amount');
                    $expense = Transaction::where('account_id', $acc->id)
                        ->whereHas('category', fn($q) => $q->where('type', 'expense'))->sum('amount');
                    
                    $acc->current_calculated_balance = ($acc->opening_balance ?? 0) + $income - $expense;
                    return $acc;
                });

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.personal-monthly', compact(
                'monthName', 'year', 'expenses', 'totalExpense',
                'needs', 'wants', 'savings', 'productive', 'consumptive', 'neutral',
                'incomes', 'totalIncome', 'netCashflow', 'accounts'
            ))->setPaper('a4', 'portrait');

            return response()->streamDownload(fn() => print($pdf->output()), "Laporan-Keluarga-{$monthName}-{$year}.pdf");
        }
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
        <div class="flex items-center justify-end gap-2">
            <button wire:click="downloadReport" class="flex items-center gap-1.5 bg-amber-600 text-zinc-100 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-zinc-700 transition shadow-sm mr-2">
                <x-heroicon-o-document-arrow-down class="w-4 h-4" /> Report
            </button>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Rentang Data : </p>
            </div>
                <select wire:model.live="selectedMonth" class="bg-white dark:bg-zinc-800 border-none text-xs font-bold rounded-lg shadow-sm focus:ring-green-500 py-1.5 px-2">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedYear" class="bg-white dark:bg-zinc-800 border-none text-xs font-bold rounded-lg shadow-sm focus:ring-green-500 py-1.5 px-2">
                    @foreach(range(now()->year - 2, now()->year) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
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
    <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden mt-6">
        
        <div class="p-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                    <x-heroicon-o-presentation-chart-line class="w-5 h-5 text-indigo-500" /> 
                    {{ $analyticMode === 'personal' ? 'Tren Arus Kas Keluarga' : 'Tren Performa Bisnis' }}
                </h4>
                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Pantau pergerakan finansial dari waktu ke waktu</p>
            </div>
            
            <div class="w-full sm:w-auto">
                <select wire:model.live="trendPeriod" class="w-full sm:w-auto bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-xs font-bold rounded-xl shadow-sm focus:ring-indigo-500 py-2 px-3 text-zinc-700 dark:text-zinc-300">
                    <option value="monthly">Bulanan (6 Bulan Terakhir)</option>
                    <option value="quarterly">Triwulanan (Per 3 Bulan)</option>
                    <option value="semester">Semester (Per 6 Bulan)</option>
                    <option value="yearly">Tahunan (Year to Year)</option>
                </select>
            </div>
        </div>
        <style>
            .apexcharts-tooltip {
                color: #18181b !important; 
            }
            .apexcharts-tooltip-title {
                color: #18181b !important;
            }
            .apexcharts-tooltip-text-y-value, 
            .apexcharts-tooltip-text-y-label {
                color: #18181b !important;
            }
            .apexcharts-legend-text {
                color: inherit !important;
            }

            html.dark .apexcharts-tooltip,
            .dark .apexcharts-tooltip {
                background-color: #18181b !important; 
                border: 1px solid #3f3f46 !important;
                color: #f4f4f5 !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5) !important;
            }
            html.dark .apexcharts-tooltip-title,
            .dark .apexcharts-tooltip-title {
                background-color: #27272a !important; 
                border-bottom: 1px solid #3f3f46 !important;
                color: #f4f4f5 !important;
            }
            html.dark .apexcharts-tooltip-text-y-value, 
            html.dark .apexcharts-tooltip-text-y-label,
            .dark .apexcharts-tooltip-text-y-value, 
            .dark .apexcharts-tooltip-text-y-label {
                color: #f4f4f5 !important;
            }

            .apexcharts-xcrosshairs, .apexcharts-ycrosshairs {
                display: none !important;
            }
        </style>
        <div class="p-5">
            <div
                wire:key="chart-{{ $analyticMode }}-{{ $trendPeriod }}-{{ $selectedBusinessId }}"
                x-data="{
                    chart: null,
                    init() {
                        if (typeof ApexCharts === 'undefined') {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                            script.onload = () => this.drawChart();
                            document.head.appendChild(script);
                        } else {
                            this.drawChart();
                        }
                    },
                    drawChart() {
                        let rawData = @js($chartData);
                        let mode = '{{ $analyticMode }}';
                        
                        if (!rawData || !rawData.series) return;

                        let chartColors = mode === 'personal'
                            ? ['#3b82f6', '#ef4444', '#10b981'] 
                            : ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'];

                        let options = {
                            chart: {
                                type: 'area',
                                height: 320,
                                toolbar: { show: false },
                                zoom: { enabled: false },
                                fontFamily: 'inherit',
                                animations: { enabled: true, easing: 'easeinout', speed: 800 }
                            },
                            colors: chartColors,
                            series: rawData.series,
                            xaxis: {
                                categories: rawData.labels,
                                tooltip: { enabled: false },
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: { style: { colors: '#9ca3af', fontSize: '11px', fontWeight: 600 } }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function (value) {
                                        if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                        if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                        return 'Rp ' + value;
                                    },
                                    style: { colors: '#9ca3af', fontSize: '11px', fontWeight: 600 }
                                }
                            },
                            dataLabels: { enabled: false },
                            stroke: { curve: 'smooth', width: 3 },
                            fill: {
                                type: 'gradient',
                                gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
                            },
                            tooltip: {
                                y: {
                                    formatter: function (val) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
                                    }
                                }
                            },
                            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', fontWeight: 600 },
                            grid: { strokeDashArray: 4, borderColor: '#e5e7eb' }
                        };

                        if (this.chart) {
                            this.chart.destroy();
                        }
                        this.chart = new ApexCharts(this.$refs.canvas, options);
                        this.chart.render();
                    }
                }"
            >
                <div x-ref="canvas" class="w-full min-h-[320px]"></div>
            </div>
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
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider mb-2">Top 5 Konsumtif Bulan Ini:</p>
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
                    <p class="text-xs text-red-700 dark:text-red-400 font-medium leading-relaxed">Lebih dari separuh uangmu ({{ $consPct }}%) hangus untuk kebutuhan konsumtif! Cek Top pengeluaran Konsumtif.</p>
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
                    
                    @php
                        // Hitung Persentase (Margin) dengan aman
                        $sales = $bizData['sales'];
                        $hppPct = $sales > 0 ? round(($bizData['hpp'] / $sales) * 100, 1) : 0;
                        $opExPct = $sales > 0 ? round(($bizData['opEx'] / $sales) * 100, 1) : 0;
                        $profitPct = $sales > 0 ? round(($bizData['profit'] / $sales) * 100, 1) : 0;
                    @endphp

                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">1. Total Penjualan</p>
                            <div class="text-right">
                                <p class="text-sm font-bold text-green-600 dark:text-green-400">+ Rp {{ number_format($sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 flex items-center gap-2">
                                2. Beli Stok / HPP
                                <span class="text-[9px] font-bold bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-1.5 py-0.5 rounded">
                                    {{ $hppPct }}%
                                </span>
                            </p>
                            <p class="text-sm font-bold text-red-500">- Rp {{ number_format($bizData['hpp'], 0, ',', '.') }}</p>
                        </div>
                        
                        <div class="flex justify-between items-center pb-3 border-b border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 flex items-center gap-2">
                                3. Biaya Operasional
                                <span class="text-[9px] font-bold bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 px-1.5 py-0.5 rounded">
                                    {{ $opExPct }}%
                                </span>
                            </p>
                            <p class="text-sm font-bold text-red-500">- Rp {{ number_format($bizData['opEx'], 0, ',', '.') }}</p>
                        </div>
                        
                        <div class="flex justify-between items-center pt-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-extrabold text-zinc-800 dark:text-zinc-100 uppercase">4. Estimasi Profit</p>
                                <span class="text-[10px] font-bold {{ $profitPct >= 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' }} px-2 py-0.5 rounded-full">
                                    Margin: {{ $profitPct }}%
                                </span>
                            </div>
                            <p class="text-lg font-extrabold {{ $bizData['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600' }}">
                                Rp {{ number_format($bizData['profit'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                    <div class="p-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 flex justify-between items-center">
                        <h4 class="text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider flex items-center gap-2">
                            <x-heroicon-o-banknotes class="w-4 h-4 text-purple-500" /> Ekuitas & Dividen
                        </h4>
                        <span class="px-2 py-0.5 bg-zinc-200 dark:bg-zinc-700 text-[9px] font-bold rounded-full text-zinc-500 uppercase">Cumulative</span>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Total Laba</p>
                                <p class="text-[10px] text-zinc-400 italic">Akumulasi sejak awal</p>
                            </div>
                            <p class="text-sm font-bold text-green-600 dark:text-green-400"> Rp {{ number_format($bizData['total_profit'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Total Prive Diambil</p>
                                <p class="text-[10px] text-zinc-400 italic">Akumulasi sejak awal</p>
                            </div>
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">- Rp {{ number_format($bizData['withdraw'], 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-dashed border-zinc-200 dark:border-zinc-700">
                            <div>
                                <p class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase">Laba Ditahan Saat Ini</p>
                                <p class="text-[10px] text-zinc-400 italic">Cadangan modal bisnis</p>
                            </div>
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
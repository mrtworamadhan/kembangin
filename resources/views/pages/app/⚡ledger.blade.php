<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Business;
use Carbon\Carbon;

new #[Layout('layouts::pwa')] class extends Component {

    public string $ledgerMode = 'personal';
    public string $selectedMonth;
    public ?int $selectedBusinessId = null;

    public function mount()
    {
        $this->selectedMonth = Carbon::now()->format('Y-m');

        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];

        $firstBusiness = Business::whereHas('users', function ($q) use ($familyIds) {
            $q->whereIn('users.id', $familyIds);
        })->first();

        if ($firstBusiness) {
            $this->selectedBusinessId = $firstBusiness->id;
        }
    }

    public function setMode($mode)
    {
        $this->ledgerMode = $mode;
    }

    public function with(): array
    {
        $user = Auth::user();
        $familyIds = $user->family_ids ?? [$user->id];

        // Parse bulan & tahun dari input
        $date = Carbon::parse($this->selectedMonth);
        $month = $date->month;
        $year = $date->year;

        // Siapkan Query Dasar
        $query = Transaction::with(['category', 'account'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        // Filter berdasarkan Mode
        if ($this->ledgerMode === 'personal') {
            $query->whereNull('business_id')->whereIn('user_id', $familyIds);
        } else {
            $query->where('business_id', $this->selectedBusinessId);
        }

        $transactions = $query->get();

        // Hitung Ringkasan (Cashflow)
        $totalIncome = $transactions->where('category.type', 'income')->sum('amount');
        $totalExpense = $transactions->where('category.type', 'expense')->sum('amount');
        $netFlow = $totalIncome - $totalExpense;

        // Grouping transaksi berdasarkan Tanggal (biar UI-nya rapi per hari)
        $groupedTransactions = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        // Ambil daftar bisnis keluarga untuk dropdown mode bisnis
        $familyBusinesses = Business::whereHas('users', function ($q) use ($familyIds) {
            $q->whereIn('users.id', $familyIds);
        })->get();

        return [
            'groupedTransactions' => $groupedTransactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netFlow' => $netFlow,
            'userBusinesses' => $familyBusinesses,
        ];
    }
};
?>

<div class="animate-fade-in space-y-5 pb-10 pt-10">
    <!-- <div class="-mt-4 mb-2">
        <a href="{{ route('app.home') }}" wire:navigate class="inline-flex items-center gap-2 bg-white backdrop-blur-md border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm rounded-full p-1 pr-3 hover:scale-[1.02] transition-transform">
            <img src="{{ asset('images/brand/logo.png') }}" alt="Home" class="w-24 h-8 object-contain">
        </a>
    </div> -->
    <div class="flex justify-between items-end">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-amber-600 to-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
                <x-heroicon-s-book-open class="w-6 h-6" />
            </div>
            <div>
                <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Buku Mutasi</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Jejak arus kas & transaksi.</p>
            </div>
        </div>
        
        <div>
            <input type="month" wire:model.live="selectedMonth" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-xl px-3 py-2 text-xs font-bold shadow-sm focus:ring-2 focus:ring-green-500 outline-none">
        </div>
    </div>

    <div class="bg-zinc-100 dark:bg-zinc-800 p-1 rounded-2xl flex w-full border border-zinc-200 dark:border-zinc-700 shadow-sm mt-2">
        <button wire:click="setMode('personal')" class="flex-1 py-2 text-sm font-bold rounded-xl transition-all {{ $ledgerMode === 'personal' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Rumah Tangga
        </button>
        <button wire:click="setMode('business')" class="flex-1 py-2 text-sm font-bold rounded-xl transition-all {{ $ledgerMode === 'business' ? 'bg-white dark:bg-zinc-700 text-green-600 dark:text-green-400 shadow-sm border border-zinc-200 dark:border-zinc-600' : 'text-zinc-500 dark:text-zinc-400' }}">
            Bisnis & Usaha
        </button>
    </div>

    @if($ledgerMode === 'business')
        <div class="relative animate-fade-in-up">
            <select wire:model.live="selectedBusinessId" class="w-full appearance-none bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 py-3 px-4 pr-10 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 font-bold text-sm">
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
    @endif

    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white dark:bg-zinc-800 p-3 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 text-center">
            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Masuk</p>
            <p class="text-xs sm:text-sm font-bold text-green-600 dark:text-green-400 truncate">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 p-3 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 text-center">
            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Keluar</p>
            <p class="text-xs sm:text-sm font-bold text-red-500 truncate">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
        </div>
        <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 p-3 rounded-2xl shadow-md border border-zinc-700 text-center text-white">
            <p class="text-[9px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Selisih (Net)</p>
            <p class="text-xs sm:text-sm font-bold {{ $netFlow >= 0 ? 'text-green-400' : 'text-red-400' }} truncate">
                {{ $netFlow < 0 ? '-' : '' }}Rp {{ number_format(abs($netFlow), 0, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="space-y-6 mt-4">
        @forelse($groupedTransactions as $date => $transactions)
            <div class="animate-fade-in-up">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-px bg-zinc-200 dark:bg-zinc-700 flex-1"></div>
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 px-3 py-1 rounded-full border border-zinc-200 dark:border-zinc-700">
                        @if($date == \Carbon\Carbon::today()->format('Y-m-d'))
                            Hari Ini
                        @elseif($date == \Carbon\Carbon::yesterday()->format('Y-m-d'))
                            Kemarin
                        @else
                            {{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}
                        @endif
                    </span>
                    <div class="h-px bg-zinc-200 dark:bg-zinc-700 flex-1"></div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
                    @foreach($transactions as $trx)
                        @php
        // Nentuin Warna Icon Berdasarkan Tipe
        if ($trx->category && $trx->category->type === 'income') {
            $iconBg = 'bg-green-50 dark:bg-green-900/30';
            $iconColor = 'text-green-600 dark:text-green-400';
            $icon = 'arrow-down-left';
            $sign = '+';
        } elseif ($trx->category && $trx->category->type === 'expense') {
            $iconBg = 'bg-red-50 dark:bg-red-900/30';
            $iconColor = 'text-red-500';
            $icon = 'arrow-up-right';
            $sign = '-';
        } else {
            $iconBg = 'bg-purple-50 dark:bg-purple-900/30';
            $iconColor = 'text-purple-600 dark:text-purple-400';
            $icon = 'arrows-right-left';
            $sign = '';
        }
                        @endphp
                        
                        <div class="p-4 flex justify-between items-center border-b border-zinc-100 dark:border-zinc-700/50 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="flex items-center gap-3 w-[65%]">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $iconBg }} {{ $iconColor }}">
                                    @if($icon == 'arrow-down-left') <x-heroicon-o-arrow-down-left class="w-5 h-5" />
                                    @elseif($icon == 'arrow-up-right') <x-heroicon-o-arrow-up-right class="w-5 h-5" />
                                    @else <x-heroicon-o-arrows-right-left class="w-5 h-5" /> @endif
                                </div>
                                <div class="truncate">
                                    <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100 truncate">
                                        {{ $trx->category->name ?? 'Tanpa Kategori' }}
                                    </p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate mt-0.5">
                                        {{ $trx->account->name ?? 'Kas Umum' }} • {{ $trx->description }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold {{ $iconColor }}">
                                    {{ $sign }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white dark:bg-zinc-800 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-700">
                <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-700 text-zinc-400 rounded-full flex items-center justify-center mx-auto mb-3">
                    <x-heroicon-o-document-magnifying-glass class="w-8 h-8" />
                </div>
                <p class="text-sm font-bold text-zinc-700 dark:text-zinc-200">Tidak ada transaksi</p>
                <p class="text-xs text-zinc-500 mt-1">Belum ada mutasi di bulan ini.</p>
            </div>
        @endforelse
    </div>

</div>
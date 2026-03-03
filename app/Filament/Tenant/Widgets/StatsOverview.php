<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '15s'; 

    protected function getHeading(): ?string
    {
        return 'Cash Flow';
    }

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()->id;
        $startOfMonth = Carbon::now()->startOfYear();
        $endOfMonth = Carbon::now()->endOfYear();

        $modal = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'income')
                ->where('name', [
                    'Suntikan Modal Tambahan',
                ]);
            })
            ->sum('amount');

        $sales = Order::where('business_id', $tenantId)
            ->sum('total_amount');

        $income = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'income')
                ->whereNotIn('name', [
                    'Transfer Masuk'
                ]);
            })
            ->sum('amount');

        $expense = Transaction::where('business_id', $tenantId)
            ->whereHas('category', fn ($q) => 
                $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Transfer Keluar'
                ]))
            ->sum('amount');

        $purchase = Purchase::where('business_id', $tenantId)
            ->sum('total_amount');

        $operationalExpense = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Bahan Baku / Pembelian Stok',
                    'Penarikan Prive / Deviden',
                    'Transfer Keluar'
                ]);
            })
            ->sum('amount');

        $saldo = $income - $expense;

        $formatCurrency = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');

        return [
            

            Stat::make('Total Uang Masuk', $formatCurrency($income))
                ->description(new HtmlString(
                    '<div class="mt-1 space-y-1 text-xs">
                        <div class="flex justify-between text-success-600">
                            <span>Modal Usaha:</span>
                            <span class="font-bold">' . $formatCurrency($modal) . '</span>
                        </div>
                        <div class="flex justify-between text-warning-600">
                            <span>Omzet Usaha:</span>
                            <span class="font-bold">' . $formatCurrency($sales) . '</span>
                        </div>
                    </div>'
                ))
                ->chart([7, 2, 10, 3, 15, 4, 2])
                ->color('success'),

            Stat::make('Total Uang Keluar', $formatCurrency($expense))
                ->description(new HtmlString(
                    '<div class="mt-1 space-y-1 text-xs">
                        <div class="flex justify-between text-success-600">
                            <span>Belanja Barang:</span>
                            <span class="font-bold">' . $formatCurrency($purchase) . '</span>
                        </div>
                        <div class="flex justify-between text-warning-600">
                            <span>Operasional:</span>
                            <span class="font-bold">' . $formatCurrency($operationalExpense) . '</span>
                        </div>
                    </div>'
                ))
                ->chart([10, 2, 10, 3, 15, 4, 17])
                ->color('danger'),

            Stat::make('Saldo', $formatCurrency($saldo))
                ->description('Saldo Kas Usaha')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldo >= 0 ? 'success' : 'danger'),

            // Stat::make('Total Order', $newOrders)
            //     ->description('Pesanan bulan ini')
            //     ->descriptionIcon('heroicon-m-shopping-cart')
            //     ->color('info'),
        ];
    }
}
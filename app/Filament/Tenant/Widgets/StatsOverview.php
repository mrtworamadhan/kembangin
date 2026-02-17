<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '15s'; 

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()->id;
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $income = Transaction::where('business_id', $tenantId)
            ->whereHas('category', fn ($q) => $q->where('type', 'income'))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expense = Transaction::where('business_id', $tenantId)
            ->whereHas('category', fn ($q) => $q->where('type', 'expense'))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $profit = $income - $expense;

        $newOrders = Order::where('business_id', $tenantId)
            ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
            ->count();

        $formatCurrency = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');

        return [
            Stat::make('Pemasukan Bulan Ini', $formatCurrency($income))
                ->description('Total uang masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Pengeluaran Bulan Ini', $formatCurrency($expense))
                ->description('Total uang keluar')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Laba Bersih (Cashflow)', $formatCurrency($profit))
                ->description('Income - Expense')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($profit >= 0 ? 'success' : 'danger'),

            Stat::make('Total Order', $newOrders)
                ->description('Pesanan bulan ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
        ];
    }
}
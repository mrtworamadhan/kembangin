<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString; // Import ini untuk HTML custom

class FinanceStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '15s';

    protected function getHeading(): ?string
    {
        return 'Rekap Tahunan';
    }

    protected function getDescription(): ?string
    {
        return 'Rekap Order Tahun Buku Berjalan';
    }

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()->id;
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $salesPaid = Order::where('business_id', $tenantId)
            ->where('payment_status', 'paid')
            ->whereBetween('order_date', [$startOfYear, $endOfYear])
            ->sum('total_amount');

        $salesUnpaid = Order::where('business_id', $tenantId)
            ->where('payment_status', 'unpaid')
            ->whereBetween('order_date', [$startOfYear, $endOfYear])
            ->sum('total_amount');

        $totalSales = $salesPaid + $salesUnpaid;

        $purchasePaid = Purchase::where('business_id', $tenantId)
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startOfYear, $endOfYear])
            ->sum('total_amount');

        $purchaseUnpaid = Purchase::where('business_id', $tenantId)
            ->where('payment_status', 'unpaid')
            ->whereBetween('date', [$startOfYear, $endOfYear])
            ->sum('total_amount');

        $totalPurchase = $purchasePaid + $purchaseUnpaid;

        $operationalExpense = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Bahan Baku / Pembelian Stok',
                    'Penarikan Prive / Deviden',
                    'Transfer Keluar'
                ]);
            })
            ->whereBetween('date', [$startOfYear, $endOfYear])
            ->sum('amount');

        $prive = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense')
                ->where('name', 'Penarikan Prive / Deviden');
            })
            ->whereBetween('date', [$startOfYear, $endOfYear])
            ->sum('amount');

        $estimatedProfit = $totalSales - $totalPurchase - $operationalExpense - $prive;

        $formatRp = fn ($val) => 'Rp ' . number_format($val, 0, ',', '.');

        $newOrders = Order::where('business_id', $tenantId)
            ->whereBetween('order_date', [$startOfYear, $endOfYear])
            ->count();
        
        return [
            Stat::make('Total Penjualan (Sales)', $formatRp($totalSales))
                ->description(new HtmlString(
                    '<div class="mt-1 space-y-1 text-xs">
                        <div class="flex justify-between text-success-600">
                            <span>✅ Lunas:</span>
                            <span class="font-bold">' . $formatRp($salesPaid) . '</span>
                        </div>
                        <div class="flex justify-between text-warning-600">
                            <span>⏳ Piutang:</span>
                            <span class="font-bold">' . $formatRp($salesUnpaid) . '</span>
                        </div>
                    </div>'
                ))
                ->color('success')
                ->chart([7, 3, 10, 5, 12, 10]),

            Stat::make('Total Belanja (Purchase)', $formatRp($totalPurchase))
                ->description(new HtmlString(
                    '<div class="mt-1 space-y-1 text-xs">
                        <div class="flex justify-between text-gray-600">
                            <span>✅ Lunas:</span>
                            <span class="font-bold">' . $formatRp($purchasePaid) . '</span>
                        </div>
                        <div class="flex justify-between text-danger-600">
                            <span>⏳ Hutang:</span>
                            <span class="font-bold">' . $formatRp($purchaseUnpaid) . '</span>
                        </div>
                    </div>'
                ))
                ->color('danger')
                ->chart([10, 5, 8, 2, 5, 12]),

            Stat::make('Estimasi Profit (Net)', $formatRp($estimatedProfit))
                ->description(new HtmlString(
                    '
                        <div class="mt-1 space-y-1 text-xs">
                            <div class="flex justify-between text-gray-600">
                                <span>Beban Ops:</span>
                                <span class="font-bold">'. $formatRp($operationalExpense) .'</span>
                            </div>
                            <div class="flex justify-between text-danger-600">
                                <span>Dividen:</span>
                                <span class="font-bold">' . $formatRp($prive) . '</span>
                            </div>
                        </div>
                    '
                ))
                ->color($estimatedProfit >= 0 ? 'success' : 'danger')
                ->chart($estimatedProfit >= 0 ? [1, 2, 5, 8, 10] : [10, 8, 5, 2, 1]),

            Stat::make('Total Order', $newOrders)
                ->description('Pesanan Tahun Ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
        ];
    }
}
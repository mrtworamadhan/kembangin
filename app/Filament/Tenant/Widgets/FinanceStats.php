<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString; // Import ini untuk HTML custom

class FinanceStats extends BaseWidget implements HasForms
{
    use InteractsWithForms;
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '15s';

    protected function getHeading(): ?string
    {
        return 'Rekap Tahunan';
    }

    protected function getDescription(): ?string
    {
        return 'Rekap Order Bulan Berjalan';
    }

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()->id;
        
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $modal = Transaction::where('business_id', $tenantId)
            ->whereHas('category', fn ($q) => $q->where('name', 'Suntikan Modal Tambahan'))
            ->sum('amount');

        $salesPaid = Order::where('business_id', $tenantId)
            ->where('payment_status', 'paid')
            ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $salesUnpaid = Order::where('business_id', $tenantId)
            ->where('payment_status', 'unpaid')
            ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $totalSales = $salesPaid + $salesUnpaid;

        $purchaseUnpaid = Purchase::where('business_id', $tenantId)

            ->where('payment_status', 'unpaid')

            ->whereBetween('date', [$startOfMonth, $endOfMonth])

            ->sum('total_amount');

        $totalHpp = \Illuminate\Support\Facades\DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $tenantId)
            ->whereBetween('orders.order_date', [$startOfMonth, $endOfMonth])
            ->sum('order_items.total_base_price');

        $operationalExpense = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Bahan Baku / Pembelian Stok',
                    'Penarikan Prive / Deviden',
                    'Transfer Keluar'
                ]);
            })
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $estimatedProfit = $totalSales - $totalHpp - $operationalExpense;

        $totalPrive = Transaction::where('business_id', $tenantId)
            ->whereHas('category', fn ($q) => $q->where('name', 'Penarikan Prive / Deviden'))
            ->sum('amount');
        
        $totalSalesAllTime = Order::where('business_id', $tenantId)->sum('total_amount');

        $totalHppAllTime = \Illuminate\Support\Facades\DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $tenantId)
            ->sum('order_items.total_base_price');

        $totalOpExAllTime = Transaction::where('business_id', $tenantId)
            ->whereHas('category', function ($q) {
                $q->where('type', 'expense')
                ->whereNotIn('name', [
                    'Bahan Baku / Pembelian Stok',
                    'Penarikan Prive / Deviden',
                    'Transfer Keluar'
                ]);
            })
            ->sum('amount');

        $totalProfitAllTime = $totalSalesAllTime - $totalHppAllTime - $totalOpExAllTime;

        $sisaProfit = $totalProfitAllTime - $totalPrive;

        $formatRp = fn ($val) => 'Rp ' . number_format($val, 0, ',', '.');

        return [
            Stat::make('Modal Usaha', $formatRp($modal))
                ->description('Total investasi owner')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('warning'),

            Stat::make('Penjualan (Bulan Ini)', $formatRp($totalSales))
                ->description(new HtmlString(
                    '<div class="mt-1 text-xs">
                        <span class="text-success-600">✅ Lunas: ' . $formatRp($salesPaid) . '</span><br>
                        <span class="text-warning-600">⏳ Piutang: ' . $formatRp($salesUnpaid) . '</span>
                    </div>'
                ))
                ->color('success')
                ->chart([7, 3, 10, 5, 12, 10]),

            Stat::make('HPP / COGS', $formatRp($totalHpp))
                ->description(new HtmlString(
                    '<div class="mt-1 text-xs">
                        <span class="text-warning-600">⏳ Hutang: ' . $formatRp($purchaseUnpaid) . '</span>
                    </div>'
                ))
                ->color('danger'),

            Stat::make('Profit Bersih (Bulan Ini)', $formatRp($estimatedProfit))
                ->description(new HtmlString(
                    '<div class="mt-1 text-xs">
                        <span class="text-gray-500">Total Est. Profit : ' . $formatRp($totalProfitAllTime) . '</span><br>
                        <span class="text-purple-600">Total Penarikan: ' . $formatRp($totalPrive) . '</span><br>
                        <span class="text-purple-600">Profit Ditahan: ' . $formatRp($sisaProfit) . '</span>
                    </div>'
                ))
                ->color($estimatedProfit >= 0 ? 'success' : 'danger')
                ->chart($estimatedProfit >= 0 ? [1, 3, 5, 8, 12] : [12, 8, 5, 3, 1]),
        ];
    }
}
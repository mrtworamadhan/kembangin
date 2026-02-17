<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class ExpenseDonutChart extends ChartWidget
{
    protected ?string $heading = 'Komposisi Pengeluaran';
    
    protected static ?int $sort = 4; 

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $tenantId = Filament::getTenant()->id;

        $data = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.business_id', $tenantId)
            ->where('categories.type', 'expense') // Filter cuma pengeluaran
            ->selectRaw('categories.name as category_name, sum(transactions.amount) as total')
            ->groupBy('categories.name')
            ->pluck('total', 'category_name'); // Hasil: ['Listrik' => 500000, 'Gaji' => 2000000]

        return [
            'datasets' => [
                [
                    'label' => 'Pengeluaran',
                    'data' => $data->values(), // Angka totalnya
                    'backgroundColor' => [
                        '#ef4444', // Red (Danger)
                        '#f59e0b', // Amber (Warning)
                        '#3b82f6', // Blue (Info)
                        '#10b981', // Green (Success)
                        '#8b5cf6', // Violet
                        '#ec4899', // Pink
                        '#6366f1', // Indigo
                        '#64748b', // Slate
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $data->keys(), // Nama Kategorinya
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Tipe Donat
    }
}
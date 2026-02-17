<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;


class SalesChart extends ChartWidget
{
    use HasFiltersSchema;
    protected ?string $heading = 'Grafik Penjualan (7 Hari Terakhir)';
    protected static ?int $sort = 3; 
    protected bool $isCollapsible = true;
    protected int | string | array $columnSpan = 4;

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('startDate')
                ->default(now()->subDays(7)),
            DatePicker::make('endDate')
                ->default(now()),
        ]);
    }

    protected function getData(): array
    {
        $tenantId = Filament::getTenant()->id;

        $filterStartDate = $this->filters['startDate'] ?? null;
        $filterEndDate = $this->filters['endDate'] ?? null;

        $startDate = $filterStartDate 
            ? Carbon::parse($filterStartDate) 
            : now()->subDays(7);

        $endDate = $filterEndDate 
            ? Carbon::parse($filterEndDate)->endOfDay() 
            : now()->endOfDay();

        $data = Trend::query(Order::where('business_id', $tenantId))
            ->between(
                start: $startDate,
                end: $endDate,
            )
            ->perDay()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,

                    'tension' => 0.4, 
                    
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6, 
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
   
    public function filtersApplyAction(Action $action): Action
    {
        return $action
            ->label('Update Chart')
            ->color('success');
    }

    protected function getType(): string
    {
        return 'line';
    }
}
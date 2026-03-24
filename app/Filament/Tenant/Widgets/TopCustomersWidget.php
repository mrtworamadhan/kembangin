<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 4; 
    protected int | string | array $columnSpan = 'full'; 
    protected static ?string $heading = '🏆 Top 5 Pelanggan Terbaik';

    public function table(Table $table): Table
    {
        $tenantId = Filament::getTenant()->id;

        return $table
            ->query(
                Customer::query()
                    ->withSum(['orders' => function ($query) use ($tenantId) {
                        $query->where('business_id', $tenantId)
                              ->where('payment_status', 'paid'); 
                    }], 'total_amount')
                    ->orderByDesc('orders_sum_total_amount') 
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->weight('bold'),
                
                TextColumn::make('phone')
                    ->label('No. HP / WA')
                    ->icon('heroicon-m-phone'),
                
                TextColumn::make('orders_sum_total_amount')
                    ->label('Total Belanja (Lunas)')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false); 
    }
}
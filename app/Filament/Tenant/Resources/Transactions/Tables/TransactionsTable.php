<?php

namespace App\Filament\Tenant\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->description(fn (Transaction $record) => $record->category->name ?? '-'), // Subtitle nama kategori

                TextColumn::make('account.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('category.type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Masuk',
                        'expense' => 'Keluar',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn (Transaction $record) => match ($record->category->type) { // <--- Cek tipe kategori
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),

            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('date', '<=', $date));
                    }),
                SelectFilter::make('type')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ]),
                SelectFilter::make('account_id')
                    ->label('Akun')
                    ->relationship('account', 'name'),
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

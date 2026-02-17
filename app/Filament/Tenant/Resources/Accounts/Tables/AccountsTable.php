<?php

namespace App\Filament\Tenant\Resources\Accounts\Tables;

use App\Models\Account;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->description(fn (Account $record) => $record->account_number ? 'No: ' . $record->account_number : null)
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank' => 'info',
                        'ewallet' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'bank' => 'Bank',
                        'ewallet' => 'E-Wallet',
                        default => $state,
                    }),

                TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->money('IDR')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true), 

                TextColumn::make('current_balance')
                    ->label('Saldo Saat Ini')
                    ->money('IDR')
                    ->weight('bold')
                    ->getStateUsing(function (Account $record) {
                        return $record->current_balance;
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'cash' => 'Tunai',
                        'bank' => 'Bank',
                        'ewallet' => 'E-Wallet',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

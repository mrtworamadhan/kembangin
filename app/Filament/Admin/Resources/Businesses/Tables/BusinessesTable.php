<?php

namespace App\Filament\Admin\Resources\Businesses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Usaha')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'personal' => 'success',
                        'business' => 'warning',
                    }),
                TextColumn::make('businessType.name')
                    ->label('Kategori Usaha')
                    ->searchable(),
                TextColumn::make('currency'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'business' => 'Bisnis',
                        'personal' => 'Pribadi',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Resources\Members\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                
                TextColumn::make('pivot_role')
                    ->label('Jabatan')
                    ->badge()
                    ->state(function (User $record) {
                        return $record->businesses()
                             ->where('business_id', Filament::getTenant()->id)
                             ->first()
                             ->pivot->role ?? '-';
                    })
                    ->colors([
                        'primary' => 'owner',
                        'success' => 'staff',
                    ]),

                TextColumn::make('created_at')->dateTime()->label('Bergabung'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Keluarkan')
                    ->modalHeading('Keluarkan Anggota')
                    ->action(function (User $record) {
                        $record->businesses()->detach(Filament::getTenant()->id);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

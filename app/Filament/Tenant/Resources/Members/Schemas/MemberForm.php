<?php

namespace App\Filament\Tenant\Resources\Members\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Anggota')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true) 
                            ->maxLength(255),

                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Select::make('role')
                            ->label('Akses / Jabatan')
                            ->options([
                                'staff' => 'Staff / Kasir',
                                'owner' => 'Owner',
                            ])
                            ->default('staff')
                            ->required()
                            ->formatStateUsing(function ($record) {
                                if (!$record) return 'staff';
                                $pivot = $record->businesses()
                                    ->where('business_id', Filament::getTenant()->id)
                                    ->first()
                                    ->pivot ?? null;
                                return $pivot ? $pivot->role : 'staff';
                            }),

                        Toggle::make('is_family_member')
                            ->label('Jadikan Anggota Keluarga (Satu Dapur)')
                            ->helperText('Aktifkan jika akun ini adalah Suami/Istri. Kas Free & Tabungan akan otomatis digabungkan.')
                            ->formatStateUsing(fn ($record) => $record && $record->household_id !== null)
                            ->default(false),
                    ])->columns(2),
            ]);
    }
}

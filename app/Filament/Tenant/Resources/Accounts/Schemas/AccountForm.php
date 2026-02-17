<?php

namespace App\Filament\Tenant\Resources\Accounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Akun')
                            ->placeholder('Contoh: Kas Toko, BCA Bisnis')
                            ->required(),

                        Select::make('type')
                            ->label('Tipe Akun')
                            ->options([
                                'cash' => 'Kas Tunai (Cash)',
                                'bank' => 'Rekening Bank',
                                'ewallet' => 'E-Wallet (Gopay/OVO/Dana)',
                            ])
                            ->default('cash')
                            ->required()
                            ->live(), 

                        TextInput::make('account_number')
                            ->label('Nomor Rekening')
                            ->hidden(fn (Get $get) => $get('type') === 'cash'),

                        TextInput::make('opening_balance')
                            ->label('Saldo Awal')
                            ->helperText('Isi saldo saat pertama kali menggunakan aplikasi ini.')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pelanggan')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Nama Lengkap'),
                        TextInput::make('email')
                            ->email()
                            ->label('Email'),
                        TextInput::make('phone')
                            ->prefix('+62 ')
                            ->tel()
                            ->label('No. HP / WhatsApp'),
                        Textarea::make('address')
                            ->columnSpanFull()
                            ->label('Alamat Lengkap'),
                    ])->columns(2),
            ]);
    }
}

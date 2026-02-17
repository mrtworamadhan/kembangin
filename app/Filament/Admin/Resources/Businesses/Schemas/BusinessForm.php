<?php

namespace App\Filament\Admin\Resources\Businesses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug'),
                Select::make('type')
                    ->options(['personal' => 'Personal', 'business' => 'Business'])
                    ->default('business')
                    ->required(),
                Select::make('business_type_id')
                    ->label('Kategori Bisnis')
                    ->relationship('businessType', 'name') // Ambil dari tabel business_types kolom name
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Bidang Usaha (Misal: Kuliner)')
                    ->required(fn (Get $get) => $get('type') === 'business')
                    ->visible(fn (Get $get) => $get('type') === 'business'),
                TextInput::make('currency')
                    ->required()
                    ->default('IDR'),
            ]);
    }
}

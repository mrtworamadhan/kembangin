<?php

namespace App\Filament\Tenant\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Supplier')
                    ->schema([
                        
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Toko/Agen')
                                ->required(),
                            TextInput::make('contact_person')
                                ->label('Nama Sales/Kontak'),
                            TextInput::make('phone')
                                ->tel()
                                ->label('No. HP / WA'),
                            Textarea::make('address')
                                ->label('Alamat')
                                ->columnSpanFull(),
                        ])
                        
                    ])->columnSpanFull(),
            ]);
    }
}

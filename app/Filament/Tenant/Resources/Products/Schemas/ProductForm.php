<?php

namespace App\Filament\Tenant\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required(),
                        TextInput::make('sku')
                            ->label('Kode (SKU)')
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->options([
                                'goods' => 'Barang Fisik',
                                'service' => 'Jasa / Layanan',
                            ])
                            ->default('goods')
                            ->reactive(),
                    ])->columns(2),

                Section::make('Harga & Stok')
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('cost')
                            ->label('Modal (HPP)')
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Hanya untuk internal, tidak muncul di nota.'),
                        TextInput::make('stock')
                            ->label('Stok Saat Ini')
                            ->numeric()
                            ->default(0)
                            ->hidden(fn (Get $get) => $get('type') === 'service'), 
                    ])->columns(3),
            ]);
    }
}

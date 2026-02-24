<?php

namespace App\Filament\Tenant\Resources\Purchases\Schemas;

use App\Models\Product;
use App\Models\Purchase;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Info Pembelian')
                            ->schema([
                                TextInput::make('number')
                                    ->label('No. PO')
                                    ->default(function () {
                                        $tenantId = Filament::getTenant()->id;
                                        
                                        $lastPurchase = Purchase::where('business_id', $tenantId)
                                            ->latest('id')
                                            ->first();

                                        if (! $lastPurchase) {
                                            return 'PO-0001';
                                        }

                                        $lastNumber = (int) substr($lastPurchase->number, 3);
                                        $newNumber = $lastNumber + 1;

                                        return 'PO-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(), 
                                
                                TextInput::make('notes')
                                    ->label('No. Nota Pembelian'),

                                Select::make('supplier_id')
                                    ->relationship('supplier', 'name') 
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([ 
                                        TextInput::make('name')->required(),
                                        TextInput::make('phone'),
                                    ]),

                                DatePicker::make('date')
                                    ->label('Tanggal Beli')
                                    ->default(now())
                                    ->required(),

                                Select::make('status')
                                    ->options([
                                        'ordered' => 'Dipesan (Barang Belum Sampai)',
                                        'received' => 'Diterima (Stok Masuk)',
                                    ])
                                    ->default('received')
                                    ->required()
                                    ->helperText('Pilih DITERIMA agar stok bertambah.'),

                                FileUpload::make('attachment')
                                    ->label('Foto Nota Asli')
                                    ->directory('purchase-notes')
                                    ->columnSpanFull(),
                                    
                            ])->columns(2),
                    ]),

                Section::make('Daftar Barang')
                    ->schema([
                        Repeater::make('items')
                            ->relationship() 
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(4)
                                    ->reactive() 
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('unit_cost', $product->cost); 
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => $set('subtotal', $state * $get('unit_cost'))),

                                TextInput::make('unit_cost')
                                    ->label('Harga Beli Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan(3)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => $set('subtotal', $state * $get('quantity'))),

                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('Rp')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $items = $get('items') ?? [];

                                $total = collect($items)->sum(function ($item) {
                                    return ((float) ($item['quantity'] ?? 0)) 
                                        * ((float) ($item['unit_cost'] ?? 0));
                                });

                                $set('total_amount', $total);
                            }),
                    ]),

                Section::make()
                    ->schema([
                        TextInput::make('total_amount')
                            ->label('Total Belanja')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->default(0),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2),
                    ]),
            ]);
    }
}

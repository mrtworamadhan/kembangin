<?php

namespace App\Filament\Tenant\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informasi Order')
                            ->schema([
                                TextInput::make('number')
                                    ->default(function () {
                                            $tenantId = Filament::getTenant()->id;

                                            $lastOrder = Order::where('business_id', $tenantId)
                                                ->latest('id')
                                                ->first();

                                            if (! $lastOrder) {
                                                return 'INV-0001';
                                            }

                                            $lastNumber = (int) substr($lastOrder->number, 4); 
                                            $newNumber = $lastNumber + 1;

                                            return 'INV-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                                        }) 
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->label('Nomor Invoice'),

                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([ 
                                        TextInput::make('name')->required(),
                                        TextInput::make('phone')                            
                                            ->prefix('+62 ')
                                            ->tel()
                                            ->label('No. HP / WhatsApp'),
                                        TextInput::make('email')
                                            ->email(),
                                    ]),

                                Select::make('status')
                                    ->options([
                                        'new' => 'Baru (Draft)',
                                        'processing' => 'Proses',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Batal',
                                    ])
                                    ->required()
                                    ->default('new'),

                                Select::make('payment_status')
                                    ->options([
                                        'unpaid' => 'Belum Bayar',
                                        'paid' => 'Lunas',
                                    ])
                                    ->default('unpaid')
                                    ->required(),
                                    
                                DatePicker::make('order_date')
                                    ->default(now())
                                    ->required(),
                            ])->columns(2),
                    ]),

                Section::make('Item Pesanan')
                    ->schema([
                        Repeater::make('items')
                            ->relationship() 
                            ->schema([
                                // Pilih Produk
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->reactive() 
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('unit_price', $product->price);
                                            $set('product_name', $product->name); 
                                        }
                                    })
                                    ->columnSpan(4),
                                
                                Hidden::make('product_name'),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive() 
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => $set('subtotal', $state * $get('unit_price')))
                                    ->columnSpan(2),

                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => $set('subtotal', $state * $get('quantity')))
                                    ->columnSpan(3),

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
                                        * ((float) ($item['unit_price'] ?? 0));
                                });

                                $set('total_amount', $total);
                            }),
                    ]),

                Section::make()
                    ->schema([
                        TextInput::make('total_amount')
                            ->label('Grand Total')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->default(0),
                        
                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->rows(3),
                    ]),
            ]);
    }
}

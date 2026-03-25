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
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class OrderForm
{
    public static function updateGrandTotal(Get $get, Set $set, bool $isInsideRepeater = false): void
    {
        $items = $isInsideRepeater ? ($get('../../items') ?? []) : ($get('items') ?? []);
        $discountGlobal = (float) ($isInsideRepeater ? ($get('../../discount_amount') ?? 0) : ($get('discount_amount') ?? 0));

        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $itemDiscount = (float) ($item['discount_amount'] ?? 0); 
            
            $itemTotal = $qty * ($price - $itemDiscount); 
            
            return $itemTotal > 0 ? $itemTotal : 0; 
        });

        $grandTotal = $subtotal - $discountGlobal;

        if ($grandTotal < 0) {
            $grandTotal = 0;
        }

        if ($isInsideRepeater) {
            $set('../../total_amount', $grandTotal);
        } else {
            $set('total_amount', $grandTotal);
        }
    }

    public static function configure(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Order')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('number')
                                ->label('Nomor Invoice')
                                ->default(function () {
                                    $tenantId = Filament::getTenant()->id;

                                    $lastOrder = Order::where('business_id', $tenantId)
                                        ->latest('id')
                                        ->first();

                                    if (!$lastOrder) {
                                        return 'INV-0001';
                                    }

                                    $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastOrder->number);
                                    $newNumber = $lastNumber + 1;

                                    return 'INV-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                                })
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->unique(
                                    ignorable: fn($record) => $record,
                                    modifyRuleUsing: function ($rule) {
                                        return $rule->where('business_id', Filament::getTenant()->id);
                                    }
                                ),

                            Select::make('customer_id')
                                ->label('Pelanggan')
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
                                ->label('Status Pesanan')
                                ->options([
                                    'new' => 'Baru (Draft)',
                                    'processing' => 'Proses',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal',
                                ])
                                ->required()
                                ->default('new'),

                            Select::make('payment_status')
                                ->label('Status Pembayaran')
                                ->options([
                                    'unpaid' => 'Belum Bayar',
                                    'paid' => 'Lunas',
                                ])
                                ->default('unpaid')
                                ->required(),

                            DatePicker::make('order_date')
                                ->label('Tanggal Order')
                                ->default(now())
                                ->required(),
                        ])

                    ])->columnSpanFull(),

                Section::make('Item Pesanan')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                                ->relationship() 
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Pilih Produk')
                                        ->relationship('product', 'name')
                                        ->required()
                                        ->live(debounce: 500) 
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $set('product_name', $product->name);
                                                
                                                $qty = (float) ($get('quantity') ?? 1);
                                                $disc = (float) ($get('discount_amount') ?? 0);
                                                $sub = $qty * ((float) $product->price - $disc);
                                                $set('subtotal', $sub > 0 ? $sub : 0);
                                                
                                                self::updateGrandTotal($get, $set, true);
                                            }
                                        })
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => fn (Get $get) => $get('is_discounted') ? 3 : 4,
                                        ]), 
                                    
                                    Hidden::make('product_name'),

                                    TextInput::make('quantity')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->live(debounce: 500) 
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $price = (float) $get('unit_price');
                                            $disc = (float) ($get('discount_amount') ?? 0);
                                            $sub = ((float) $state) * ($price - $disc);
                                            $set('subtotal', $sub > 0 ? $sub : 0);
                                            
                                            self::updateGrandTotal($get, $set, true);
                                        })
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => 2,
                                        ]),

                                    TextInput::make('unit_price')
                                        ->label('Harga Satuan')
                                        
                                        ->numeric()
                                        ->required()
                                        ->prefix('Rp')
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $qty = (float) $get('quantity');
                                            $disc = (float) ($get('discount_amount') ?? 0);
                                            $sub = $qty * (((float) $state) - $disc);
                                            $set('subtotal', $sub > 0 ? $sub : 0);
                                            
                                            self::updateGrandTotal($get, $set, true);
                                        })
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => fn (Get $get) => $get('is_discounted') ? 2 : 3,
                                        ]),

                                    Toggle::make('is_discounted')
                                        ->label('Disc?')
                                        ->inline(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (!$state) {
                                                $set('discount_amount', 0);
                                                $qty = (float) $get('quantity');
                                                $price = (float) $get('unit_price');
                                                $sub = $qty * $price; 
                                                $set('subtotal', $sub > 0 ? $sub : 0);
                                                
                                                self::updateGrandTotal($get, $set, true);
                                            }
                                        })
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => 1,
                                        ]),

                                    TextInput::make('discount_amount')
                                        ->label('Diskon/Pcs')
                                        
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('Rp')
                                        ->live(debounce: 500)
                                        ->visible(fn (Get $get) => $get('is_discounted'))
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $qty = (float) $get('quantity');
                                            $price = (float) $get('unit_price');
                                            $disc = (float) $state;
                                            $sub = $qty * ($price - $disc);
                                            $set('subtotal', $sub > 0 ? $sub : 0);
                                            
                                            self::updateGrandTotal($get, $set, true);
                                        })
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => 2,
                                        ]),

                                    TextInput::make('subtotal')
                                        ->label('Subtotal')
                                        
                                        ->numeric()
                                        ->readOnly()
                                        ->prefix('Rp')
                                        ->columnSpan([
                                            'default' => 12,
                                            'md' => 2,
                                        ]),
                                ])
                                ->columns(12)
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateGrandTotal($get, $set, false);
                                }),
                    ]),

                Section::make('Diskon & Cashback (Global)')
                    ->schema([
                        Toggle::make('is_discounted')
                            ->label('Tambahkan Diskon / Cashback?')
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (!$state) {
                                    $set('discount_amount', 0);
                                    self::updateGrandTotal($get, $set, false);
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('discount_amount')
                            ->label('Nominal Diskon (Rp)')
                            
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateGrandTotal($get, $set, false);
                            })
                            ->required(fn(Get $get) => $get('is_discounted'))
                            ->visible(fn(Get $get) => $get('is_discounted')),

                        TextInput::make('discount_note')
                            ->label('Keterangan Diskon')
                            ->placeholder('Contoh: Promo Lebaran, Cashback Pelanggan Setia')
                            ->maxLength(255)
                            ->required(fn(Get $get) => $get('is_discounted'))
                            ->visible(fn(Get $get) => $get('is_discounted')),
                    ])->columns(2),

                Section::make('Ringkasan')
                    ->schema([
                        TextInput::make('total_amount')
                            ->label('Grand Total (Setelah Diskon)')
                            
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->default(0)
                            ->extraInputAttributes(['class' => 'font-bold text-lg text-primary-600']),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan (Internal)')
                            ->rows(3),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Tenant\Resources\Transactions\Schemas;

use App\Models\Category;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Input Transaksi')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'income' => 'Pemasukan (Income)',
                                'expense' => 'Pengeluaran (Expense)',
                            ])
                            ->required()
                            ->live() 
                            ->dehydrated(false) 
                            ->afterStateHydrated(function (Select $component, $state, ? Transaction $record) {
                                if ($record && $record->category) {
                                    $component->state($record->category->type);
                                }
                            })
                            ->afterStateUpdated(fn (Set $set) => $set('category_id', null)),
                        
                        Select::make('account_id')
                            ->label('Akun / Dompet')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name') 
                            ->options(function (Get $get) {
                                $type = $get('type'); 
                                $tenantId = Filament::getTenant()->id;

                                if (! $type) {
                                    return [];
                                }

                                return Category::query()
                                    ->where('type', $type) 
                                    ->where('group', 'business')
                                    ->where(function ($query) use ($tenantId) {
                                        $query->where('business_id', $tenantId)
                                            ->orWhereNull('business_id');
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                Hidden::make('type')->default(fn (Get $get) => $get('type')),
                                Hidden::make('group')->default('business'),
                            ]),

                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(999999999999)
                            ->required(),

                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->placeholder('Contoh: Bayar Listrik Bulan Maret')
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Bukti Struk/Nota')
                            ->image()
                            ->directory('transaction-proofs')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}

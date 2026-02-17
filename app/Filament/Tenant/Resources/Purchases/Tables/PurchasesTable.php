<?php

namespace App\Filament\Tenant\Resources\Purchases\Tables;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable(),
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attachment')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('receive')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang')
                    ->modalDescription('Stok produk akan bertambah sesuai jumlah di nota. Pastikan barang sudah dicek!')
                    ->visible(fn (Purchase $record) => $record->status === 'ordered')
                    ->action(function (Purchase $record) {
                        if ($record->business->use_stock_management) {
                            foreach ($record->items as $item) {
                                $product = Product::find($item->product_id);
                                if ($product) {
                                    $product->increment('stock', $item->quantity);
                                    $product->update(['cost' => $item->unit_cost]);
                                }
                            }
                        }

                        $record->update(['status' => 'received']);

                        Notification::make()
                            ->title('Stok Berhasil Ditambahkan')
                            ->success()
                            ->send();
                    }),

                Action::make('pay')
                    ->label('Bayar Lunas')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger') 
                    ->requiresConfirmation()
                    ->form([
                        Select::make('account_id')
                            ->label('Sumber Dana')
                            ->options(fn () => Account::where('business_id', Filament::getTenant()->id)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->visible(fn (Purchase $record) => $record->payment_status === 'unpaid')
                    ->action(function (Purchase $record, array $data) {
                        $category = Category::firstOrCreate(
                            ['name' => 'Pembelian Stok', 'business_id' => $record->business_id],
                            ['type' => 'expense', 'group' => 'business']
                        );

                        Transaction::create([
                            'business_id' => $record->business_id,
                            'account_id' => $data['account_id'],
                            'category_id' => $category->id,
                            'amount' => $record->total_amount,
                            'date' => now(),
                            'description' => 'Pembayaran PO #' . $record->number,
                        ]);

                        $record->update(['payment_status' => 'paid']);

                        Notification::make()->title('Pembayaran Berhasil Dicatat')->success()->send();
                    }),
                EditAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

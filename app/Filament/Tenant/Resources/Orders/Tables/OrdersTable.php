<?php

namespace App\Filament\Tenant\Resources\Orders\Tables;

use App\Models\Account;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->badge(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Action::make('pdf')
                //     ->label('Download PDF')
                //     ->icon('heroicon-o-document-arrow-down')
                //     ->color('warning')
                //     ->action(function (Order $record) {
                //         $business = $record->business;
                //         $theme = $record->business->invoice_theme ?? 'modern';
                //         $color = $business->invoice_color ?? '#F59E0B';
                        
                //         $pdf = Pdf::loadView('invoices.' . $theme, [
                //             'order' => $record,
                //             'color' => $color,    
                //             'logo' => $business->logo,
                //         ]);

                //         $pdf->setPaper('a4', 'portrait');

                //         return response()->streamDownload(function () use ($pdf) {
                //             echo $pdf->output();
                //         }, 'Invoice-' . $record->number . '.pdf');
                //     }),
                Action::make('preview_pdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-document')
                    ->color('warning')
                    ->url(fn (Order $record) => route('invoice.preview', $record))
                    ->openUrlInNewTab(),
                Action::make('payment')
                    ->label('Terima Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('account_id')
                            ->label('Masuk ke Akun?')
                            ->options(fn () => Account::where('business_id', Filament::getTenant()->id)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->visible(fn (Order $record) => $record->payment_status === 'unpaid')
                    ->action(function (Order $record, array $data) {
                        
                        if ($record->business->use_stock_management) {
                            foreach ($record->items as $item) {
                                $product = Product::find($item->product_id);
                                if ($product && $product->stock >= $item->quantity) {
                                    $product->decrement('stock', $item->quantity);
                                } elseif ($product) {
                                    $product->decrement('stock', $item->quantity);
                                    Notification::make()->title('Peringatan: Stok Produk ' . $product->name . ' jadi Minus!')->warning()->send();
                                }
                            }
                        }

                        $category = Category::firstOrCreate(
                            ['name' => 'Penjualan Produk', 'business_id' => $record->business_id],
                            ['type' => 'income', 'group' => 'business']
                        );

                        Transaction::create([
                            'business_id' => $record->business_id,
                            'account_id' => $data['account_id'],
                            'category_id' => $category->id,
                            'amount' => $record->total_amount,
                            'order_id' => $record->id,
                            'date' => now(),
                            'description' => 'Pembayaran Invoice #' . $record->number,
                        ]);

                        $record->update([
                            'payment_status' => 'paid',
                            'status' => 'completed',
                        ]);

                        Notification::make()->title('Pembayaran Diterima & Stok Berkurang')->success()->send();
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

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
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),

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
                
            ])
            ->defaultSort('order_date', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF INV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->form([
                        Toggle::make('show_discount')
                            ->label('Tampilkan Rincian Diskon?')
                            ->default(true)
                            ->helperText('Jika dimatikan, invoice tidak akan menampilkan kata "Diskon". Harga item akan otomatis ditampilkan sebagai Harga Netto.'),
                    ])
                    ->action(function (Order $record, array $data) { 
                        $business = $record->business;
                        $theme = $record->business->invoice_theme ?? 'modern';
                        $color = $business->invoice_color ?? '#F59E0B';
                        $accounts = $business->accounts()
                            ->whereNotNull('account_number')
                            ->where('account_number', '!=', '')
                            ->get();
                        
                        $pdf = Pdf::loadView('invoices.' . $theme, [
                            'order' => $record,
                            'color' => $color,    
                            'logo' => $business->logo,
                            'accounts' => $accounts,
                            'show_discount' => $data['show_discount'], 
                        ]);

                        $pdf->setPaper('a4', 'portrait');

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Invoice-' . $record->number . '.pdf');
                    }),

                Action::make('kwitansi')
                    ->label('Kwitansi')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    // Tombol ini HANYA muncul kalau sudah dibayar
                    ->visible(fn (Order $record) => $record->payment_status === 'paid')
                    ->form([
                        Toggle::make('show_discount')
                            ->label('Tampilkan Harga Diskon?')
                            ->default(true)
                            ->helperText('Jika dimatikan, nominal kuitansi akan mencatat total harga normal (seolah tidak ada diskon).'),
                    ])
                    ->action(function (Order $record, array $data) {
                        $business = $record->business;
                        $color = $business->invoice_color ?? '#F59E0B';
                        
                        $pdf = Pdf::loadView('invoices.kwitansi', [
                            'order' => $record,
                            'color' => $color,    
                            'logo' => $business->logo,
                            'show_discount' => $data['show_discount'], 
                        ]);

                        // Kuitansi biasanya formatnya memanjang (Landscape) ukuran setengah A4 (A5)
                        $pdf->setPaper('a4', 'landscape');

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Kwitansi-' . $record->number . '.pdf');
                    }),
                Action::make('send_wa')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->form([
                        Toggle::make('show_discount')
                            ->label('Tampilkan Rincian Diskon di PDF?')
                            ->default(true),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Invoice via WhatsApp?')
                    ->modalDescription('Pilih format invoice, lalu sistem akan membuatkan link PDF dan mengarahkan Anda ke WhatsApp.')
                    ->action(function (Order $record, array $data) { 
                        
                        $business = $record->business;
                        $theme = $business->invoice_theme ?? 'modern';
                        $color = $business->invoice_color ?? '#F59E0B';
                        $accounts = $business->accounts()
                            ->whereNotNull('account_number')
                            ->where('account_number', '!=', '')
                            ->get();

                        $pdf = Pdf::loadView('invoices.' . $theme, [
                            'order' => $record,
                            'color' => $color,    
                            'logo' => $business->logo,
                            'accounts' => $accounts,
                            'show_discount' => $data['show_discount'],
                        ])->setPaper('a4', 'portrait');

                        $fileName = 'invoices/Invoice-' . $record->number . '.pdf';
                        Storage::disk('public')->put($fileName, $pdf->output());
                        $fileUrl = asset('storage/' . $fileName);

                        $phone = $record->customer->phone ?? ''; 
                        $customer = $record->customer->name ?? '';
                        if (empty($phone)) {
                            Notification::make()
                                ->title('Gagal Mengirim')
                                ->body('Nomor WhatsApp pelanggan tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $phone = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }

                        $message = "Halo {$customer}, \n\nBerikut adalah tagihan (Invoice) untuk pesanan Anda: *{$record->number}*.\n\n";
                        $message .= "Anda dapat melihat dan mengunduh invoice melalui tautan berikut:\n";
                        $message .= $fileUrl . "\n\n";
                        $message .= "Terima kasih telah bertransaksi dengan *{$business->name}*.";

                        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

                        Notification::make()
                            ->title('Link Invoice Siap Dikirim!')
                            ->body('Klik tombol di bawah untuk membuka WhatsApp.')
                            ->success()
                            ->persistent()
                            ->actions([
                                Action::make('buka_wa')
                                    ->label('Buka WhatsApp Sekarang')
                                    ->button()
                                    ->color('success')
                                    ->url($waUrl, shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }),

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
                            ['name' => 'Penjualan Produk'],
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

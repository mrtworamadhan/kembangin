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
use Illuminate\Support\Facades\Storage;

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
                Action::make('pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function (Order $record) {
                        $business = $record->business;
                        $theme = $record->business->invoice_theme ?? 'modern';
                        $color = $business->invoice_color ?? '#F59E0B';
                        
                        $pdf = Pdf::loadView('invoices.' . $theme, [
                            'order' => $record,
                            'color' => $color,    
                            'logo' => $business->logo,
                        ]);

                        $pdf->setPaper('a4', 'portrait');

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'Invoice-' . $record->number . '.pdf');
                    }),
                Action::make('send_wa')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Invoice via WhatsApp?')
                    ->modalDescription('Sistem akan membuatkan link PDF dan mengarahkan Anda ke WhatsApp Web/App.')
                    ->action(function (Order $record) {
                        
                        // 1. Ambil Data (Sama seperti fungsi download)
                        $business = $record->business;
                        $theme = $business->invoice_theme ?? 'modern';
                        $color = $business->invoice_color ?? '#F59E0B';
                        
                        // 2. Render PDF
                        $pdf = Pdf::loadView('invoices.' . $theme, [
                            'order' => $record,
                            'color' => $color,    
                            'logo' => $business->logo,
                        ])->setPaper('a4', 'portrait');

                        // 3. Simpan PDF ke folder public secara fisik
                        $fileName = 'invoices/Invoice-' . $record->number . '.pdf';
                        Storage::disk('public')->put($fileName, $pdf->output());

                        // 4. Dapatkan URL Publik dari PDF tersebut
                        $fileUrl = asset('storage/' . $fileName);

                        // 5. Cek & Format Nomor WA Pelanggan
                        $phone = $record->customer->phone ?? ''; 
                        
                        if (empty($phone)) {
                            Notification::make()
                                ->title('Gagal Mengirim')
                                ->body('Nomor WhatsApp pelanggan tidak ditemukan di data transaksi ini.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Format nomor (0812 jadi 62812)
                        $phone = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }

                        // 6. Buat Teks Pesan WA
                        $message = "Halo Bapak/Ibu, \n\nBerikut adalah tagihan (Invoice) untuk pesanan Anda: *{$record->number}*.\n\n";
                        $message .= "Anda dapat melihat dan mengunduh invoice melalui tautan berikut:\n";
                        $message .= $fileUrl . "\n\n";
                        $message .= "Terima kasih telah mempercayakan bisnis Anda kepada *{$business->name}*.";

                        $waUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

                        // 7. Munculkan Notifikasi dengan tombol Buka WA (Agar bisa buka di Tab Baru)
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
                // Action::make('preview_pdf')
                //     ->label('Preview PDF')
                //     ->icon('heroicon-o-document')
                //     ->color('warning')
                //     ->url(fn (Order $record) => route('invoice.preview', $record))
                //     ->openUrlInNewTab(),
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

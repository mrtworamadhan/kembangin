<?php

namespace App\Filament\Tenant\Resources\Customers\RelationManagers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('business_id')
                    ->required()
                    ->numeric(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
                            'new' => 'New',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                    ->default('new')
                    ->required(),
                Select::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'])
                    ->default('unpaid')
                    ->required(),
                DatePicker::make('order_date')
                    ->required(),
                DatePicker::make('due_date'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number'),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('payment_status')
                    ->badge(),
                TextEntry::make('order_date')
                    ->date(),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')
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
                TextColumn::make('total_amount')
                    ->label('Total Transaksi')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Pembelanjaan')
                            ->numeric()
                            ->prefix('Rp ')
                    ),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('print_batch')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('invoice.print-batch', $record->number))
                    ->openUrlInNewTab(),
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
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}

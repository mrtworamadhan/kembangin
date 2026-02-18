<?php

namespace App\Filament\Tenant\Resources\Accounts\Pages;

use App\Filament\Tenant\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transfer')
                ->label('Transfer Kas')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->form([
                    Select::make('from_account_id')
                        ->label('Dari Rekening / Kas Asal')
                        ->options(fn () => Account::where('business_id', Filament::getTenant()->id)->pluck('name', 'id'))
                        ->required(),
                    
                    Select::make('to_account_id')
                        ->label('Ke Rekening / Kas Tujuan')
                        ->options(fn () => Account::where('business_id', Filament::getTenant()->id)->pluck('name', 'id'))
                        ->different('from_account_id') 
                        ->required(),
                    
                    TextInput::make('amount')
                        ->label('Nominal Transfer (Rp)')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('Rp')
                        ->required(),
                    
                    TextInput::make('description')
                        ->label('Catatan (Opsional)')
                        ->maxLength(255)
                        ->placeholder('Contoh: Setor tunai hasil shift pagi ke BCA'),
                ])
                ->modalHeading('Transfer Antar Kas Bisnis')
                ->modalDescription('Pindahkan saldo antar dompet tanpa mempengaruhi total omzet dan pengeluaran toko.')
                ->modalSubmitActionLabel('Transfer Sekarang')
                ->action(function (array $data) {
                    $businessId = Filament::getTenant()->id;
                    $userId = auth()->id();

                    DB::transaction(function () use ($data, $businessId, $userId) {
                        
                        $catOut = Category::firstOrCreate(
                            ['business_id' => $businessId, 'name' => 'Transfer Keluar', 'type' => 'expense'],
                            ['user_id' => $userId, 'group' => 'business']
                        );

                        $catIn = Category::firstOrCreate(
                            ['business_id' => $businessId, 'name' => 'Transfer Masuk', 'type' => 'income'],
                            ['user_id' => $userId, 'group' => 'business']
                        );

                        $notes = $data['description'] ?: 'Transfer antar rekening bisnis';

                        Transaction::create([
                            'business_id' => $businessId,
                            'user_id' => $userId,
                            'account_id' => $data['from_account_id'],
                            'category_id' => $catOut->id,
                            'amount' => $data['amount'],
                            'type' => 'expense',
                            'date' => now(),
                            'description' => $notes,
                        ]);

                        Transaction::create([
                            'business_id' => $businessId,
                            'user_id' => $userId,
                            'account_id' => $data['to_account_id'],
                            'category_id' => $catIn->id,
                            'amount' => $data['amount'],
                            'type' => 'income',
                            'date' => now(),
                            'description' => $notes,
                        ]);
                    });

                })
                ->successNotificationTitle('Transfer berhasil dicatat!'),
            CreateAction::make(),
        ];
    }
}

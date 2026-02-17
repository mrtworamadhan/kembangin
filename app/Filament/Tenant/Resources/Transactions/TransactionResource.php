<?php

namespace App\Filament\Tenant\Resources\Transactions;

use App\Filament\Tenant\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Tenant\Resources\Transactions\Pages\EditTransaction;
use App\Filament\Tenant\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Tenant\Resources\Transactions\Schemas\TransactionForm;
use App\Filament\Tenant\Resources\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string | UnitEnum | null $navigationGroup = 'Transaction';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $title = 'Catatan Transaksi';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
            'edit' => EditTransaction::route('/{record}/edit'),
        ];
    }
}

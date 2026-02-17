<?php

namespace App\Filament\Tenant\Resources\Purchases;

use App\Filament\Tenant\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Tenant\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Tenant\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Tenant\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Tenant\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = 'Transaction';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Pemesanan';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
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
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }
}

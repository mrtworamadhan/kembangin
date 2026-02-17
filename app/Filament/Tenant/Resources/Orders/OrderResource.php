<?php

namespace App\Filament\Tenant\Resources\Orders;

use App\Filament\Tenant\Resources\Orders\Pages\CreateOrder;
use App\Filament\Tenant\Resources\Orders\Pages\EditOrder;
use App\Filament\Tenant\Resources\Orders\Pages\ListOrders;
use App\Filament\Tenant\Resources\Orders\Schemas\OrderForm;
use App\Filament\Tenant\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string | UnitEnum | null $navigationGroup = 'Transaction';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Invoice';
    protected static ?string $title = 'Daftar Order';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}

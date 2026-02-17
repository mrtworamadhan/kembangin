<?php

namespace App\Filament\Tenant\Resources\Suppliers\Pages;

use App\Filament\Tenant\Resources\Suppliers\SupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

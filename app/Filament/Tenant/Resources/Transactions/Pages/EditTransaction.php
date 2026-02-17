<?php

namespace App\Filament\Tenant\Resources\Transactions\Pages;

use App\Filament\Tenant\Resources\Transactions\TransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

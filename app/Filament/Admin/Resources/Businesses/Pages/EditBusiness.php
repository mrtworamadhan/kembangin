<?php

namespace App\Filament\Admin\Resources\Businesses\Pages;

use App\Filament\Admin\Resources\Businesses\BusinessResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

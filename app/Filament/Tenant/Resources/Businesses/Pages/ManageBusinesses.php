<?php

namespace App\Filament\Tenant\Resources\Businesses\Pages;

use App\Filament\Tenant\Resources\Businesses\BusinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBusinesses extends ManageRecords
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

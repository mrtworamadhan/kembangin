<?php

namespace App\Filament\Admin\Resources\Businesses\Pages;

use App\Filament\Admin\Resources\Businesses\BusinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

}

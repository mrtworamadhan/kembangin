<?php

namespace App\Filament\Tenant\Resources\Members\Pages;

use App\Filament\Tenant\Resources\Members\MemberResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = $data['role'];
        $isFamily = $data['is_family_member'] ?? false;

        unset($data['role'], $data['is_family_member']);

        if ($isFamily) {
            $me = auth()->user();
            $data['household_id'] = $me->household_id ?? $me->id;
        } else {
            $data['household_id'] = null; 
        }

        $record->update($data);

        $record->businesses()->updateExistingPivot(Filament::getTenant()->id, [
            'role' => $role,
        ]);

        return $record;
    }
}

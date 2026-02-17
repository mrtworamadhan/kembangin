<?php

namespace App\Filament\Tenant\Resources\Members\Pages;

use App\Filament\Tenant\Resources\Members\MemberResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'];
        $isFamily = $data['is_family_member'] ?? false;
        
        unset($data['is_family_member']);

        if ($isFamily) {
            $me = auth()->user();
            $data['household_id'] = $me->household_id ?? $me->id;
        } else {
            $data['household_id'] = null;
        }

        $data['status'] = 'active';

        $user = static::getModel()::create($data);

        $user->businesses()->attach(Filament::getTenant()->id, ['role' => $role]);

        return $user;
    }
}

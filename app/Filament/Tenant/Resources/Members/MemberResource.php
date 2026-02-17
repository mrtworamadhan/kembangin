<?php

namespace App\Filament\Tenant\Resources\Members;

use App\Filament\Tenant\Resources\Members\Pages\CreateMember;
use App\Filament\Tenant\Resources\Members\Pages\EditMember;
use App\Filament\Tenant\Resources\Members\Pages\ListMembers;
use App\Filament\Tenant\Resources\Members\Schemas\MemberForm;
use App\Filament\Tenant\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $tenantOwnershipRelationshipName = 'businesses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Tim Saya';
    protected static ?string $label = 'Anggota Tim';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        return parent::getEloquentQuery()->whereHas('businesses', function ($query) use ($tenant) {
            $query->where('business_id', $tenant->id);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
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
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}

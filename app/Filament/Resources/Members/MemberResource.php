<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Filament\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static string | UnitEnum | null $navigationGroup = 'Manage';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getGloballySearchableAttributes(): array
    {
        return ['cid', 'first_name', 'last_name', 'email'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'cid', 'first_name', 'last_name', 'middle_name', 'branch_number', 'is_migs', 'is_registered', 'created_at'])
            ->where('is_active', true) // Ensure you're only selecting active members
            ->orderBy('last_name', 'asc')
            ->with(['branch:id,branch_number,branch_name']); // Eager load the branch relation

        return $query;
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        // Return a concise title for the global search result
        return $record->last_name . ', ' . $record->first_name . ' ' . $record->middle_name; // Full name
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        // Return a list of details for the global search result
        return [
            __('CID') => $record->cid ? $record->cid : 'NULL', // Member's CID
            __('Is Migs') => $record->is_migs ? 'Yes' : 'No', // If the member is registered
            __('Is Registered') => $record->is_registered ? 'Yes' : 'No', // If the member is registered
            __('Branch') => $record->branch ? $record->branch->branch_name : 'No Branch', // Display the branch name, if exists
        ];
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

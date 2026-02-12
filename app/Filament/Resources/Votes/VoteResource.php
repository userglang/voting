<?php

namespace App\Filament\Resources\Votes;

use App\Filament\Resources\Votes\Pages\CreateVote;
use App\Filament\Resources\Votes\Pages\EditVote;
use App\Filament\Resources\Votes\Pages\ListVotes;
use App\Filament\Resources\Votes\Schemas\VoteForm;
use App\Filament\Resources\Votes\Tables\VotesTable;
use App\Models\Candidate;
use App\Models\Vote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CheckCircle;

    protected static string | UnitEnum | null $navigationGroup = 'Manage';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return VoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VotesTable::configure($table);
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
            'index' => ListVotes::route('/'),
            'create' => CreateVote::route('/create'),
            'edit' => EditVote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    // Add this method to handle validation before save
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Check for duplicate vote (same branch, member, and candidate)
        $duplicate = Vote::where('branch_number', $data['branch_number'])
            ->where('member_code', $data['member_code'])
            ->where('candidate_id', $data['candidate_id'])
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'candidate_id' => 'This member has already voted for this candidate in this branch.',
            ]);
        }

        // Get candidate and check position vote limit
        $candidate = Candidate::with('position')->find($data['candidate_id']);

        if ($candidate) {
            $limitCheck = Vote::checkPositionVoteLimit(
                $data['member_code'],
                $data['branch_number'],
                $candidate->position_id
            );

            if (!$limitCheck['allowed']) {
                throw ValidationException::withMessages([
                    'candidate_id' => $limitCheck['message'],
                ]);
            }
        }

        // Get or set control number for this member in this branch
        $existingVote = Vote::where('branch_number', $data['branch_number'])
            ->where('member_code', $data['member_code'])
            ->first();

        if ($existingVote) {
            // Use the same control number
            $data['control_number'] = $existingVote->control_number;
        }
        // If no existing vote, the model's boot method will auto-generate a new control number

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Same validation for edit
        return static::mutateFormDataBeforeCreate($data);
    }
}

<?php

namespace App\Filament\Resources\Votes\Schemas;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Member;
use App\Models\Position;
use App\Models\Vote;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

class VoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // CREATE/EDIT MODE SECTION
                Section::make('Member, Branch & Candidate Selection')
                    ->description('Select the member, branch, and candidate')
                    ->icon('heroicon-o-user-group')
                    ->columnSpanFull()
                    ->visible(fn ($context, $record) => in_array($context, ['create', 'edit']))
                    ->columns(3)
                    ->schema([
                        // Branch Selection
                        Select::make('branch_number')
                            ->label('Branch')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(function () {
                                return Branch::where('is_active', true)
                                    ->orderBy('branch_name')
                                    ->get()
                                    ->mapWithKeys(fn ($branch) => [
                                        $branch->branch_number => "{$branch->branch_name} "
                                    ]);
                            })
                            ->helperText('Select the branch where this vote was cast')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Reset member and candidate when branch changes
                                $set('member_code', null);

                                // Show branch statistics
                                if ($state) {
                                    $branch = Branch::where('branch_number', $state)->first();
                                    $eligibleCount = Member::where('branch_number', $state)
                                        ->where('is_active', true)
                                        ->where('is_registered', true)
                                        ->where('is_migs', true)
                                        ->where('share_amount', '>', 0)
                                        ->where('process_type', 'Updating and Voting')
                                        ->count();

                                    $votedCount = Vote::where('branch_number', $state)->count();

                                    Notification::make()
                                        ->title("Branch: {$branch->branch_name}")
                                        ->body("Eligible voters: {$eligibleCount} | Votes cast: {$votedCount}")
                                        ->info()
                                        ->duration(5000)
                                        ->send();
                                }
                            })
                            ->columnSpan(1),

                        // Member Selection with enhanced filtering
                        Select::make('member_code')
                            ->label('Member')
                            ->required()
                            ->searchable(['code', 'first_name', 'last_name', 'middle_name'])
                            ->preload()
                            ->native(false)
                            ->getSearchResultsUsing(function (string $search, callable $get) {
                                $branchNumber = $get('branch_number');

                                if (!$branchNumber) {
                                    return [];
                                }

                                return Member::where('branch_number', $branchNumber)
                                    ->where('is_active', true)
                                    ->where('is_registered', true)
                                    ->where('is_migs', true)
                                    ->where('share_amount', '>', 0)
                                    ->where('process_type', 'Updating and Voting')
                                    ->where(function ($query) use ($search) {
                                        $query->where('code', 'like', "%{$search}%")
                                            ->orWhere('first_name', 'like', "%{$search}%")
                                            ->orWhere('last_name', 'like', "%{$search}%")
                                            ->orWhere('middle_name', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($member) => [
                                        $member->code => "{$member->full_name}"
                                    ]);
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $member = Member::where('code', $value)->first();
                                return $member ? "{$member->full_name}" : $value;
                            })
                            ->helperText('Search by name or member code')
                            ->disabled(fn (callable $get) => !$get('branch_number'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $member = Member::where('code', $state)->first();

                                    if ($member) {
                                        // Get existing votes for this member in this branch
                                        $branchNumber = $get('branch_number');
                                        $existingVote = Vote::where('member_code', $state)
                                            ->where('branch_number', $branchNumber)
                                            ->first();

                                        if ($existingVote) {
                                            // Set the same control number
                                            $set('control_number', $existingVote->control_number);
                                        }

                                        // Check vote count
                                        $voteCount = Vote::where('member_code', $state)
                                            ->where('branch_number', $branchNumber)
                                            ->count();

                                        if ($voteCount > 0) {
                                            Notification::make()
                                                ->warning()
                                                ->title('Member Already Voted')
                                                ->body("{$member->full_name} has already cast {$voteCount} vote(s) in this branch. Control Number: #{$existingVote->control_number}")
                                                ->persistent()
                                                ->send();
                                        }

                                        // Check eligibility
                                        if (!$member->is_migs) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Not MIGS Member')
                                                ->body('This member is not a MIGS member and may not be eligible to vote.')
                                                ->persistent()
                                                ->send();
                                        }

                                        if ($member->share_amount <= 0) {
                                            Notification::make()
                                                ->danger()
                                                ->title('No Share Amount')
                                                ->body('This member has no share amount.')
                                                ->persistent()
                                                ->send();
                                        }
                                    }
                                }
                            })
                            ->columnSpan(1),

                        // Position Filter for Candidates
                        Select::make('position_filter')
                            ->label('Filter by Position')
                            ->options(function () {
                                return Position::where('is_active', true)
                                    ->orderBy('title')
                                    ->get()
                                    ->mapWithKeys(fn ($position) => [
                                        $position->id => "{$position->title}"
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('All Positions')
                            ->disabled(fn (callable $get) => !$get('member_code'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Reset candidate when position filter changes
                                $set('candidate_id', null);

                                // Show position vote limit info
                                if ($state) {
                                    $memberCode = $get('member_code');
                                    $branchNumber = $get('branch_number');

                                    if ($memberCode && $branchNumber) {
                                        $limitCheck = Vote::checkPositionVoteLimit($memberCode, $branchNumber, $state);

                                        $notificationType = $limitCheck['allowed'] ? 'info' : 'warning';

                                        Notification::make()
                                            ->$notificationType()
                                            ->title('Vote Limit for Position')
                                            ->body($limitCheck['message'])
                                            ->duration(6000)
                                            ->send();
                                    }
                                }
                            })
                            ->helperText('Filter candidates by position')
                            ->dehydrated(false) // Don't save this field
                            ->columnSpan(1),

                        // Member Info Display (real-time)
                        Placeholder::make('member_details_live')
                            ->label('Selected Member Details')
                            ->content(function (callable $get) {
                                $memberCode = $get('member_code');

                                if (!$memberCode) {
                                    return new HtmlString('<span class="text-gray-400 italic text-sm">Select a member to view details</span>');
                                }

                                $member = Member::where('code', $memberCode)->first();

                                if (!$member) {
                                    return new HtmlString('<span class="text-red-500 text-sm">Member not found</span>');
                                }

                                $statusBadges = collect([
                                    $member->is_active ? '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Active</span>' : '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">Inactive</span>',
                                    $member->is_migs ? '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">MIGS</span>' : '',
                                    $member->is_registered ? '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Registered</span>' : '',
                                ])->filter()->join(' ');

                                return new HtmlString("
                                    <div class='bg-gradient-to-br from-blue-50 to-purple-50 p-4 rounded-lg border border-blue-200'>
                                        <div class='flex justify-between items-start'>
                                            <div>
                                                <div class='font-semibold text-base text-gray-900'>{$member->full_name}</div>
                                            </div>
                                            <div class='flex gap-1 flex-wrap justify-end'>
                                                {$statusBadges}
                                            </div>
                                        </div>
                                        <div class='grid grid-cols-2 gap-2 text-sm mt-2'>
                                            <div><span class='font-medium'>Branch:</span> {$member->branch->branch_name}</div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpan(3)
                            ->visible(fn (callable $get) => $get('member_code')),

                        // Candidate Selection with position filtering
                        Select::make('candidate_id')
                            ->label('Candidate')
                            ->required()
                            ->searchable(['first_name', 'last_name', 'middle_name'])
                            ->preload()
                            ->native(false)
                            ->disabled(fn (callable $get) => !$get('member_code'))
                            ->options(function (callable $get) {
                                $positionFilter = $get('position_filter');

                                $query = Candidate::with('position')
                                    ->whereHas('position', fn ($q) => $q->where('is_active', true));

                                if ($positionFilter) {
                                    $query->where('position_id', $positionFilter);
                                }

                                return $query->get()
                                    ->sortBy(fn($candidate) => $candidate->position->title)
                                    ->mapWithKeys(fn ($candidate) => [
                                        $candidate->id => "{$candidate->full_name} - {$candidate->position->title}"
                                    ]);
                            })
                            ->helperText('Select the candidate who received this vote')
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                "{$record->full_name} - {$record->position->title}"
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $branchNumber = $get('branch_number');
                                $memberCode = $get('member_code');

                                if (!$branchNumber || !$memberCode || !$state) {
                                    return;
                                }

                                // Get candidate and position
                                $candidate = Candidate::with('position')->find($state);

                                if (!$candidate) {
                                    return;
                                }

                                // Check for exact duplicate vote
                                $duplicate = Vote::where('branch_number', $branchNumber)
                                    ->where('member_code', $memberCode)
                                    ->where('candidate_id', $state)
                                    ->first();

                                if ($duplicate) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Duplicate Vote Detected!')
                                        ->body('This member has already voted for this candidate in this branch.')
                                        ->persistent()
                                        ->send();

                                    $set('candidate_id', null);
                                    return;
                                }

                                // Check position vote limit
                                $limitCheck = Vote::checkPositionVoteLimit(
                                    $memberCode,
                                    $branchNumber,
                                    $candidate->position_id
                                );

                                if (!$limitCheck['allowed']) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Vote Limit Reached!')
                                        ->body($limitCheck['message'])
                                        ->persistent()
                                        ->send();

                                    $set('candidate_id', null);
                                    return;
                                }

                                // Show remaining votes info
                                if ($limitCheck['current_votes'] > 0) {
                                    Notification::make()
                                        ->info()
                                        ->title('Position Vote Status')
                                        ->body($limitCheck['message'])
                                        ->duration(5000)
                                        ->send();
                                }
                            })
                            ->columnSpan(3),

                        // Candidate Preview (real-time)
                        Placeholder::make('candidate_preview')
                            ->label('Candidate Preview')
                            ->content(function (callable $get) {
                                $candidateId = $get('candidate_id');

                                if (!$candidateId) {
                                    return new HtmlString('<span class="text-gray-400 italic text-sm">Select a candidate to view details</span>');
                                }

                                $candidate = Candidate::with('position')->find($candidateId);

                                if (!$candidate) {
                                    return new HtmlString('<span class="text-red-500 text-sm">Candidate not found</span>');
                                }

                                $imageUrl = $candidate->profile_image_url;
                                $voteCount = Vote::where('candidate_id', $candidateId)->count();
                                $position = $candidate->position;

                                $imageHtml = $imageUrl
                                    ? "<img src='{$imageUrl}' alt='{$candidate->full_name}' class='w-24 h-24 rounded-lg object-cover border-2 border-gray-200'>"
                                    : "<div class='w-24 h-24 rounded-lg bg-gray-200 flex items-center justify-center border-2 border-gray-300'>
                                        <svg class='w-12 h-12 text-gray-400' fill='currentColor' viewBox='0 0 20 20'>
                                            <path fill-rule='evenodd' d='M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z' clip-rule='evenodd'/>
                                        </svg>
                                    </div>";

                                return new HtmlString("
                                    <div class='bg-gradient-to-br from-blue-50 to-purple-50 p-4 rounded-lg border border-blue-200'>
                                        <div class='flex gap-4'>
                                            {$imageHtml}
                                            <div class='flex-1 space-y-2'>
                                                <div>
                                                    <div class='font-bold text-lg text-gray-900'>{$candidate->full_name}</div>
                                                    <div class='text-sm font-medium text-blue-600'>{$position->title}</div>
                                                    <div class='text-xs text-gray-500 mt-1'>Max votes allowed: {$position->vacant_count}</div>
                                                </div>
                                                <div class='text-sm text-gray-700 line-clamp-2'>{$candidate->background_profile}</div>
                                                <div class='flex items-center gap-2 text-sm'>
                                                    <span class='px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium'>
                                                        Votes Received: {$voteCount}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpan(3)
                            ->visible(fn (callable $get) => $get('candidate_id')),
                    ]),

                Section::make('Vote Information & Session Details')
                    ->description('Voting record and session details')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        // Control Number Display
                        Placeholder::make('control_number_display')
                            ->label('Control Number')
                            ->content(fn ($record) => $record?->control_number
                                ? new HtmlString('
                                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg shadow-md">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-mono text-xl font-bold">#' . str_pad($record->control_number, 6, '0', STR_PAD_LEFT) . '</span>
                                    </div>
                                ')
                                : new HtmlString('<span class="text-gray-400 italic">Auto-generated on save</span>'))
                            ->columnSpan(1)
                            ->visible(fn ($context) => in_array($context, ['view', 'edit'])),

                        // Hidden field for actual control_number
                        TextInput::make('control_number')
                            ->hidden()
                            ->default(null),

                        // Online Vote Toggle
                        Toggle::make('online_vote')
                            ->label('Online Vote')
                            ->helperText('Enable if this vote was cast online')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),

                        // Vote Type Badge
                        Placeholder::make('vote_type')
                            ->label('Vote Type')
                            ->content(fn ($record) => $record?->online_vote
                                ? new HtmlString('
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>
                                        </svg>
                                        Online Vote
                                    </span>
                                ')
                                : new HtmlString('
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-gray-500 to-gray-600 text-white shadow-sm">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                                        </svg>
                                        Offline Vote
                                    </span>
                                '))
                            ->visible(fn ($context) => $context === 'view')
                            ->columnSpan(1),

                        // Vote Cast Date
                        Placeholder::make('created_at')
                            ->label('Vote Cast On')
                            ->content(fn ($record) => $record?->created_at
                                ? new HtmlString("
                                    <div class='text-sm'>
                                        <div class='font-semibold text-gray-900'>{$record->created_at->format('F d, Y')}</div>
                                        <div class='text-gray-500'>{$record->created_at->format('g:i A')}</div>
                                        <div class='text-xs text-gray-400 mt-1'>{$record->created_at->diffForHumans()}</div>
                                    </div>
                                ")
                                : 'N/A')
                            ->columnSpan(1),

                        // Last Updated Date
                        Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at
                                ? new HtmlString("
                                    <div class='text-sm'>
                                        <div class='font-semibold text-gray-900'>{$record->updated_at->format('F d, Y')}</div>
                                        <div class='text-gray-500'>{$record->updated_at->format('g:i A')}</div>
                                        <div class='text-xs text-gray-400 mt-1'>{$record->updated_at->diffForHumans()}</div>
                                    </div>
                                ")
                                : 'N/A')
                            ->columnSpan(1),

                        // Branch Information
                        Placeholder::make('branch_info')
                            ->label('Branch Information')
                            ->content(function ($record) {
                                if (!$record || !$record->branch) {
                                    return new HtmlString('<span class="text-gray-400 italic">N/A</span>');
                                }

                                $branch = $record->branch;
                                $branchVoteCount = Vote::where('branch_number', $branch->branch_number)->count();

                                return new HtmlString("
                                    <div >
                                        <div class='font-semibold text-gray-900'>{$branch->branch_name}</div>
                                        <div class='text-sm text-gray-600 mt-1'>Branch #{$branch->branch_number}</div>
                                        <div class='text-xs text-gray-500 mt-2'>Total votes from this branch: {$branchVoteCount}</div>
                                    </div>
                                ");
                            })
                            ->columnSpan(1),
                    ]),

            ]);
    }
}

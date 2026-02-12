<?php

namespace App\Filament\Pages;

use App\Models\Member;
use App\Models\Position;
use App\Models\Vote;
use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class VotingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCursorArrowRays;
    protected static ?string $navigationLabel = 'Voting';
    protected string $view = 'filament.pages.voting-page';


    protected static string | UnitEnum | null $navigationGroup = 'Page';
    protected static ?int $navigationSort = 1;

    public $positions = [];
    public $selectedVotes = [];
    public $currentMember = null;
    public $hasAlreadyVoted = false;
    public $previousVotes = [];
    public $isEligibleToVote = false;
    public $ineligibilityReason = '';
    public $controlNumber = null;

    public function mount(): void
    {
        // Get current user
        $user = Auth::user();

        // Get member record (update this logic based on how you link users to members)
        // For now, using a sample member - you should update this based on your user-member relationship
        $member = Member::where('is_active', true)
            ->where('is_registered', true)
            ->first();

        if (!$member) {
            $this->isEligibleToVote = false;
            $this->ineligibilityReason = 'Member record not found. Please contact administrator.';
            return;
        }

        $this->currentMember = $member;

        // Check member eligibility to vote
        $eligibilityCheck = $this->checkMemberEligibility($member);

        if (!$eligibilityCheck['eligible']) {
            $this->isEligibleToVote = false;
            $this->ineligibilityReason = $eligibilityCheck['reason'];
            return;
        }

        $this->isEligibleToVote = true;

        // Check if member has already voted
        $existingVotes = Vote::where('member_code', $member->code)
            ->where('branch_number', $member->branch_number)
            ->with('candidate.position')
            ->get();

        if ($existingVotes->isNotEmpty()) {
            $this->hasAlreadyVoted = true;

            // Get the control number (all votes should have the same control number)
            $this->controlNumber = $existingVotes->first()->control_number;

            // Group votes by position
            $this->previousVotes = $existingVotes->groupBy(function($vote) {
                return $vote->candidate->position_id;
            })->map(function($votes) {
                return $votes->map(function($vote) {
                    return [
                        'candidate_id' => $vote->candidate_id,
                        'candidate_name' => $vote->candidate->full_name,
                        'candidate_image' => $vote->candidate->profile_image_url,
                        'candidate_background' => $vote->candidate->background_profile,
                        'position_title' => $vote->candidate->position->title,
                    ];
                })->toArray();
            })->toArray();

            // Load only the positions and candidates that were voted for
            $votedPositionIds = $existingVotes->pluck('candidate.position_id')->unique();

            $this->positions = Position::query()
                ->whereIn('id', $votedPositionIds)
                ->orderBy('priority')
                ->with(['candidates' => function ($query) use ($existingVotes) {
                    $votedCandidateIds = $existingVotes->pluck('candidate_id');
                    $query->whereIn('id', $votedCandidateIds)
                          ->orderBy('last_name');
                }])
                ->get()
                ->map(function ($position) {
                    return [
                        'id' => $position->id,
                        'title' => $position->title,
                        'vacant_count' => $position->vacant_count,
                        'priority' => $position->priority,
                        'candidates' => $position->candidates->map(function ($candidate) {
                            return [
                                'id' => $candidate->id,
                                'full_name' => $candidate->full_name,
                                'background_profile' => $candidate->background_profile,
                                'profile_image_url' => $candidate->profile_image_url,
                            ];
                        })->toArray(),
                    ];
                })
                ->toArray();

            return;
        }

        // If not voted yet, load all active positions with their candidates
        $this->positions = Position::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->with(['candidates' => function ($query) {
                $query->orderBy('last_name');
            }])
            ->get()
            ->map(function ($position) {
                return [
                    'id' => $position->id,
                    'title' => $position->title,
                    'vacant_count' => $position->vacant_count,
                    'priority' => $position->priority,
                    'candidates' => $position->candidates->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'full_name' => $candidate->full_name,
                            'background_profile' => $candidate->background_profile,
                            'profile_image_url' => $candidate->profile_image_url,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();

        // Initialize selected votes array
        foreach ($this->positions as $position) {
            $this->selectedVotes[$position['id']] = [];
        }
    }

    /**
     * Check if member is eligible to vote based on business rules
     */
    protected function checkMemberEligibility(Member $member): array
    {
        // Rule 1: Member must be MIGS (is_migs = true)
        if (!$member->is_migs) {
            return [
                'eligible' => false,
                'reason' => 'You are not a MIGS member. Only MIGS members are eligible to vote.',
            ];
        }

        // Rule 2: Member share_amount must be greater than 0
        if ($member->share_amount <= 0) {
            return [
                'eligible' => false,
                'reason' => 'Your share amount is zero. Members must have share contributions to vote.',
            ];
        }

        // Rule 3: Process type must be "Updating and Voting"
        if ($member->process_type !== 'Updating and Voting') {
            return [
                'eligible' => false,
                'reason' => 'Your process type is "' . ($member->process_type ?? 'Not Set') . '". Only members with "Updating and Voting" process type can vote.',
            ];
        }

        // Rule 4: Member must be active
        if (!$member->is_active) {
            return [
                'eligible' => false,
                'reason' => 'Your membership is inactive. Please contact administrator.',
            ];
        }

        // Rule 5: Member must be registered
        if (!$member->is_registered) {
            return [
                'eligible' => false,
                'reason' => 'You are not registered. Please complete registration first.',
            ];
        }

        // All checks passed
        return [
            'eligible' => true,
            'reason' => '',
        ];
    }

    public function getHeading(): string
    {
        if (!$this->isEligibleToVote) {
            return 'Voting Not Available';
        }

        if ($this->hasAlreadyVoted) {
            return 'Your Votes';
        }

        return 'Cast Your Vote';
    }

    public function getSubheading(): ?string
    {
        if (!$this->isEligibleToVote) {
            return null;
        }

        if ($this->hasAlreadyVoted) {
            return 'Thank you for participating in the election';
        }

        return 'Select your preferred candidates for each position';
    }

    public function toggleVote(string $positionId, string $candidateId): void
    {
        if (!$this->isEligibleToVote) {
            Notification::make()
                ->danger()
                ->title('Not Eligible')
                ->body($this->ineligibilityReason)
                ->send();
            return;
        }

        if ($this->hasAlreadyVoted) {
            Notification::make()
                ->warning()
                ->title('Already Voted')
                ->body('You have already cast your vote.')
                ->send();
            return;
        }

        $position = collect($this->positions)->firstWhere('id', $positionId);

        if (!$position) {
            return;
        }

        $vacantCount = $position['vacant_count'];
        $currentVotes = $this->selectedVotes[$positionId] ?? [];

        // Check if candidate is already selected
        if (in_array($candidateId, $currentVotes)) {
            // Remove the vote
            $this->selectedVotes[$positionId] = array_values(
                array_filter($currentVotes, fn($id) => $id !== $candidateId)
            );
        } else {
            // Check if we've reached the limit
            if (count($currentVotes) >= $vacantCount) {
                Notification::make()
                    ->warning()
                    ->title('Maximum Votes Reached')
                    ->body("You can only vote for {$vacantCount} candidate(s) for this position.")
                    ->send();
                return;
            }

            // Add the vote
            $this->selectedVotes[$positionId][] = $candidateId;
        }
    }

    public function isSelected(string $positionId, string $candidateId): bool
    {
        return in_array($candidateId, $this->selectedVotes[$positionId] ?? []);
    }

    public function getVoteCount(string $positionId): int
    {
        return count($this->selectedVotes[$positionId] ?? []);
    }

    public function canVoteMore(string $positionId): bool
    {
        $position = collect($this->positions)->firstWhere('id', $positionId);
        if (!$position) {
            return false;
        }

        return $this->getVoteCount($positionId) < $position['vacant_count'];
    }

    public function submitVotes(): void
    {
        // Check eligibility again
        if (!$this->isEligibleToVote) {
            Notification::make()
                ->danger()
                ->title('Not Eligible')
                ->body($this->ineligibilityReason)
                ->send();
            return;
        }

        // Check if already voted
        if ($this->hasAlreadyVoted) {
            Notification::make()
                ->warning()
                ->title('Already Voted')
                ->body('You have already cast your vote.')
                ->send();
            return;
        }

        // Validate that user has voted for at least one position
        if ($this->getTotalSelectedVotes() === 0) {
            Notification::make()
                ->warning()
                ->title('No Votes Selected')
                ->body('Please select at least one candidate before submitting.')
                ->send();
            return;
        }

        // Get member
        $member = $this->currentMember;

        if (!$member) {
            Notification::make()
                ->danger()
                ->title('Member Not Found')
                ->body('Unable to find your member record.')
                ->send();
            return;
        }

        // Double-check eligibility before saving
        $eligibilityCheck = $this->checkMemberEligibility($member);
        if (!$eligibilityCheck['eligible']) {
            Notification::make()
                ->danger()
                ->title('Not Eligible')
                ->body($eligibilityCheck['reason'])
                ->send();
            return;
        }

        try {
            // Generate ONE control number for this member's voting session
            // Get the next control number for this branch and member
            $lastControlNumber = Vote::where('branch_number', $member->branch_number)
                ->where('member_code', $member->code)
                ->max('control_number');

            $controlNumber = ($lastControlNumber ?? 0) + 1;

            // Save all votes with the SAME control number
            foreach ($this->selectedVotes as $positionId => $candidateIds) {
                foreach ($candidateIds as $candidateId) {
                    Vote::create([
                        'control_number' => $controlNumber,
                        'branch_number' => $member->branch_number,
                        'member_code' => $member->code,
                        'candidate_id' => $candidateId,
                        'online_vote' => true,
                    ]);
                }
            }

            Notification::make()
                ->success()
                ->title('Vote Submitted')
                ->body('Thank you! Your vote has been recorded successfully. Control Number: ' . $controlNumber)
                ->send();

            // Reload the page to show voted candidates
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to submit your vote. Please try again.')
                ->send();
        }
    }

    public function getTotalSelectedVotes(): int
    {
        return array_sum(array_map('count', $this->selectedVotes));
    }

    public function getTotalAvailableVotes(): int
    {
        return array_sum(array_column($this->positions, 'vacant_count'));
    }

    public function getMemberInfo(): array
    {
        if (!$this->currentMember) {
            return [];
        }

        return [
            'name' => $this->currentMember->full_name,
            'code' => $this->currentMember->code,
            'branch' => $this->currentMember->branch->branch_name ?? 'N/A',
            'is_migs' => $this->currentMember->is_migs,
            'share_amount' => $this->currentMember->share_amount,
            'process_type' => $this->currentMember->process_type,
        ];
    }
}

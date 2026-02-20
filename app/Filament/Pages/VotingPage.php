<?php

namespace App\Filament\Pages;

use App\Models\Position;
use App\Models\Vote;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class VotingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;
    protected static ?string $navigationLabel = 'Voting';
    protected string $view = 'filament.pages.voting-page';
    protected static string|UnitEnum|null $navigationGroup = 'Page';
    protected static ?int $navigationSort = 1;

    // -------------------------------------------------------------------------
    // TEST MODE — hardcoded voter identity. No Member/Auth/User lookup at all.
    // When going to production, replace these constants and restore real auth.
    // -------------------------------------------------------------------------
    private const TEST_MEMBER_CODE   = 'TEST-001';
    private const TEST_BRANCH_NUMBER = 'BR-001';

    public array  $positions           = [];
    public array  $selectedVotes       = [];
    public array  $previousVotes       = [];
    public bool   $hasAlreadyVoted     = false;
    public bool   $isEligibleToVote    = true;
    public string $ineligibilityReason = '';
    public ?int   $controlNumber       = null;

    // Displayed in the view — no DB hit needed.
    public array $memberInfo = [
        'name'         => 'Test Voter',
        'code'         => self::TEST_MEMBER_CODE,
        'branch'       => 'Test Branch',
        'is_migs'      => true,
        'share_amount' => 1000,
        'process_type' => 'Updating and Voting',
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $this->loadVotingState();
    }

    // -------------------------------------------------------------------------
    // State loading
    // -------------------------------------------------------------------------

    protected function loadVotingState(): void
    {
        $existingVotes = Vote::where('member_code', self::TEST_MEMBER_CODE)
            ->where('branch_number', self::TEST_BRANCH_NUMBER)
            ->with(['candidate.position'])
            ->get();

        if ($existingVotes->isNotEmpty()) {
            $this->hasAlreadyVoted = true;
            $this->controlNumber   = $existingVotes->first()->control_number;
            $this->buildPreviousVotes($existingVotes);
            $this->loadVotedPositions($existingVotes);
            return;
        }

        $this->hasAlreadyVoted = false;
        $this->loadBallot();
    }

    protected function buildPreviousVotes(\Illuminate\Support\Collection $existingVotes): void
    {
        $this->previousVotes = $existingVotes
            ->groupBy(fn ($vote) => $vote->candidate->position_id)
            ->map(fn ($votes) =>
                $votes->map(fn ($vote) => [
                    'candidate_id'         => $vote->candidate_id,
                    'candidate_name'       => $vote->candidate->full_name,
                    'candidate_image'      => $vote->candidate->profile_image_url,
                    'candidate_background' => $vote->candidate->background_profile,
                    'position_title'       => $vote->candidate->position->title,
                ])->toArray()
            )
            ->toArray();
    }

    protected function loadVotedPositions(\Illuminate\Support\Collection $existingVotes): void
    {
        $votedPositionIds  = $existingVotes->pluck('candidate.position_id')->unique();
        $votedCandidateIds = $existingVotes->pluck('candidate_id')->unique();

        $this->positions = Position::query()
            ->whereIn('id', $votedPositionIds)
            ->orderBy('priority')
            ->with(['candidates' => fn ($q) =>
                $q->whereIn('id', $votedCandidateIds)->orderBy('last_name')
            ])
            ->get()
            ->map(fn ($p) => $this->mapPosition($p))
            ->toArray();
    }

    protected function loadBallot(): void
    {
        $positions = Position::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->with(['candidates' => fn ($q) => $q->orderBy('last_name')])
            ->get();

        $this->positions     = $positions->map(fn ($p) => $this->mapPosition($p))->toArray();
        $this->selectedVotes = collect($this->positions)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => []])
            ->toArray();
    }

    protected function mapPosition(Position $position): array
    {
        return [
            'id'           => $position->id,
            'title'        => $position->title,
            'vacant_count' => $position->vacant_count,
            'priority'     => $position->priority,
            'candidates'   => $position->candidates->map(fn ($c) => [
                'id'                 => $c->id,
                'full_name'          => $c->full_name,
                'background_profile' => $c->background_profile,
                'profile_image_url'  => $c->profile_image_url,
            ])->toArray(),
        ];
    }

    // -------------------------------------------------------------------------
    // Livewire actions
    // -------------------------------------------------------------------------

    public function toggleVote(string $positionId, string $candidateId): void
    {
        if ($this->hasAlreadyVoted) {
            Notification::make()->warning()->title('Already Voted')->body('You have already cast your vote.')->send();
            return;
        }

        $position = collect($this->positions)->firstWhere('id', $positionId);
        if (! $position) {
            return;
        }

        $vacantCount  = $position['vacant_count'];
        $currentVotes = $this->selectedVotes[$positionId] ?? [];

        if (in_array($candidateId, $currentVotes)) {
            $this->selectedVotes[$positionId] = array_values(
                array_filter($currentVotes, fn ($id) => $id !== $candidateId)
            );
        } else {
            if (count($currentVotes) >= $vacantCount) {
                Notification::make()
                    ->warning()
                    ->title('Maximum Votes Reached')
                    ->body("You can only vote for {$vacantCount} candidate(s) for this position.")
                    ->send();
                return;
            }
            $this->selectedVotes[$positionId][] = $candidateId;
        }
    }

    public function submitVotes(): void
    {
        if ($this->hasAlreadyVoted) {
            Notification::make()->warning()->title('Already Voted')->body('You have already cast your vote.')->send();
            return;
        }

        if ($this->getTotalSelectedVotes() === 0) {
            Notification::make()->warning()->title('No Votes Selected')->body('Please select at least one candidate before submitting.')->send();
            return;
        }

        try {
            DB::transaction(function () {
                $alreadyVoted = Vote::where('member_code', self::TEST_MEMBER_CODE)
                    ->where('branch_number', self::TEST_BRANCH_NUMBER)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyVoted) {
                    $this->hasAlreadyVoted = true;
                    throw new \RuntimeException('duplicate_vote');
                }

                $controlNumber = (Vote::where('branch_number', self::TEST_BRANCH_NUMBER)
                    ->lockForUpdate()
                    ->max('control_number') ?? 0) + 1;

                $now  = now();
                $rows = [];

                foreach ($this->selectedVotes as $positionId => $candidateIds) {
                    foreach ($candidateIds as $candidateId) {
                        $rows[] = [
                            'control_number' => $controlNumber,
                            'branch_number'  => self::TEST_BRANCH_NUMBER,
                            'member_code'    => self::TEST_MEMBER_CODE,
                            'candidate_id'   => $candidateId,
                            'online_vote'    => true,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }
                }

                Vote::insert($rows);

                $this->controlNumber   = $controlNumber;
                $this->hasAlreadyVoted = true;
            });

            Notification::make()
                ->success()
                ->title('Vote Submitted')
                ->body('Test vote recorded. Control Number: ' . $this->controlNumber)
                ->send();

            $this->loadVotingState();

        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_vote') {
                Notification::make()->warning()->title('Already Voted')->body('This test voter already has a recorded vote.')->send();
            } else {
                Log::error('VotingPage::submitVotes', ['error' => $e->getMessage()]);
                Notification::make()->danger()->title('Error')->body('Failed to submit. Please try again.')->send();
            }
        } catch (\Exception $e) {
            Log::error('VotingPage::submitVotes', ['error' => $e->getMessage()]);
            Notification::make()->danger()->title('Error')->body('Failed to submit. Please try again.')->send();
        }
    }

    // -------------------------------------------------------------------------
    // View helpers
    // -------------------------------------------------------------------------

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
        return $position && $this->getVoteCount($positionId) < $position['vacant_count'];
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
        return $this->memberInfo;
    }

    public function getHeading(): string
    {
        return $this->hasAlreadyVoted ? 'Your Votes' : 'Cast Your Vote';
    }

    public function getSubheading(): ?string
    {
        return $this->hasAlreadyVoted
            ? 'Thank you for participating in the election.'
            : 'Select your preferred candidates for each position.';
    }
}

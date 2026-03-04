<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Vote extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'votes';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'control_number',
        'branch_number',
        'member_code',
        'candidate_id',
        'online_vote',
        'vote_version',
        'is_valid',
    ];

    protected $casts = [
        'online_vote'  => 'boolean',
        'is_valid'     => 'boolean',
        'vote_version' => 'integer',
    ];

    /**
     * Auto-generate UUID, control number, and handle revote logic.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vote) {

            DB::transaction(function () use ($vote) {

                // Generate UUID
                if (empty($vote->id)) {
                    $vote->id = (string) Str::uuid();
                }

                // Default is_valid
                $vote->is_valid = $vote->is_valid ?? true;

                // -------------------------------
                // Control Number Logic
                // -------------------------------
                if (is_null($vote->control_number)) {
                    $existingVote = static::where('branch_number', $vote->branch_number)
                        ->where('member_code', $vote->member_code)
                        ->first();

                    if ($existingVote) {
                        $vote->control_number = $existingVote->control_number;
                    } else {
                        $maxControlNumber = static::where('branch_number', $vote->branch_number)
                            ->max('control_number') ?? 0;
                        $vote->control_number = $maxControlNumber + 1;
                    }
                }

                // -------------------------------
                // Revote Window Logic
                // -------------------------------
                $candidate = Candidate::with('position')->find($vote->candidate_id);

                if ($candidate && $candidate->position) {

                    $position = $candidate->position;

                    // Find active revote window for this position
                    $revoteWindow = RevoteWindow::where('position_id', $position->id)
                        ->where('start_at', '<=', now())
                        ->where('end_at', '>=', now())
                        ->first();

                    $validVotesQuery = static::where('member_code', $vote->member_code)
                        ->where('branch_number', $vote->branch_number)
                        ->where('is_valid', true)
                        ->whereHas('candidate', fn($q) => $q->where('position_id', $position->id));

                    // If revote window exists, restrict to eligible original votes
                    if ($revoteWindow) {
                        $validVotesQuery->whereBetween('created_at', [$revoteWindow->start_at, $revoteWindow->end_at]);
                    }

                    $validVotes = $validVotesQuery->orderBy('created_at')->get();

                    $maxVotes = $position->vacant_count ?? 1;

                    // Set vote_version
                    $vote->vote_version = ($validVotes->max('vote_version') ?? 0) + 1;

                    // If member already reached max votes, invalidate oldest vote (FIFO)
                    if ($validVotes->count() >= $maxVotes) {
                        $oldestVote = $validVotes->first();
                        $oldestVote->update(['is_valid' => false]);
                    }
                }
            });
        });
    }

    // -------------------------------
    // Relationships
    // -------------------------------
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_number', 'branch_number');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_code', 'code');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    // -------------------------------
    // Check Position Vote Limit (valid votes only)
    // -------------------------------
    public static function checkPositionVoteLimit(string $memberCode, string $branchNumber, string $positionId): array
    {
        $position = Position::find($positionId);

        if (!$position) {
            return [
                'allowed'       => false,
                'message'       => 'Position not found',
                'current_votes' => 0,
                'max_votes'     => 0,
            ];
        }

        $currentVotes = static::where('member_code', $memberCode)
            ->where('branch_number', $branchNumber)
            ->where('is_valid', true)
            ->whereHas('candidate', fn($q) => $q->where('position_id', $positionId))
            ->count();

        $maxVotes = $position->vacant_count ?? 1;

        return [
            'allowed'       => $currentVotes < $maxVotes,
            'message'       => $currentVotes >= $maxVotes
                ? "Maximum votes ({$maxVotes}) reached for {$position->title}"
                : "Can vote " . ($maxVotes - $currentVotes) . " more time(s) for {$position->title}",
            'current_votes' => $currentVotes,
            'max_votes'     => $maxVotes,
            'position_title'=> $position->title,
        ];
    }
}

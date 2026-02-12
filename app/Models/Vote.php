<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Vote extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'votes';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'control_number',
        'branch_number',
        'member_code',
        'candidate_id',
        'online_vote',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'online_vote' => 'boolean',
    ];

    /**
     * Auto-generate UUID for the primary key.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vote) {
            // Generate a UUID if it doesn't exist
            if (empty($vote->id)) {
                $vote->id = (string) Str::uuid();
            }

            // Auto-generate or reuse the control_number
            if (is_null($vote->control_number)) {
                // Check if this member already has a control number in this branch
                $existingVote = static::where('branch_number', $vote->branch_number)
                    ->where('member_code', $vote->member_code)
                    ->first();

                if ($existingVote) {
                    // Reuse the existing control number
                    $vote->control_number = $existingVote->control_number;
                } else {
                    // Generate a new control number (increment from max in this branch)
                    $maxControlNumber = static::where('branch_number', $vote->branch_number)
                        ->max('control_number') ?? 0;

                    $vote->control_number = $maxControlNumber + 1;
                }
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public static function checkPositionVoteLimit(string $memberCode, string $branchNumber, string $positionId): array
    {
        $position = Position::find($positionId);

        if (!$position) {
            return [
                'allowed' => false,
                'message' => 'Position not found',
                'current_votes' => 0,
                'max_votes' => 0,
            ];
        }

        $currentVotes = static::where('member_code', $memberCode)
            ->where('branch_number', $branchNumber)
            ->whereHas('candidate', function ($query) use ($positionId) {
                $query->where('position_id', $positionId);
            })
            ->count();

        $maxVotes = $position->vacant_count ?? 1;

        return [
            'allowed' => $currentVotes < $maxVotes,
            'message' => $currentVotes >= $maxVotes
                ? "Maximum votes ({$maxVotes}) reached for {$position->title}"
                : "Can vote " . ($maxVotes - $currentVotes) . " more time(s) for {$position->title}",
            'current_votes' => $currentVotes,
            'max_votes' => $maxVotes,
            'position_title' => $position->title,
        ];
    }
}

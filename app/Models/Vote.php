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
        'member_id',
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

            // Auto-generate the control_number if it's not provided
            if (is_null($vote->control_number)) {
                // Find the max control_number for this branch and member combination
                $vote->control_number = static::where('branch_number', $vote->branch_number)
                    ->where('member_id', $vote->member_id)
                    ->max('control_number') + 1;
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
        return $this->belongsTo(Member::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}

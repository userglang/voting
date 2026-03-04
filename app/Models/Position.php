<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Position extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'positions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'vacant_count',
        'priority',
        'is_active',
        'is_for_revote',
    ];

    protected $casts = [
        'vacant_count'   => 'integer',
        'priority'       => 'integer',
        'is_active'      => 'boolean',
        'is_for_revote'  => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function revoteWindows()
    {
        return $this->hasMany(RevoteWindow::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Revote Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Check if there is an active revote window right now.
     */
    public function isRevoteActive(): bool
    {
        return $this->revoteWindows()
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->exists();
    }

    /**
     * Check if position is open for normal voting.
     */
    public function isVotingActive(): bool
    {
        return $this->is_active && !$this->isRevoteActive();
    }

    /**
     * Determine if voting (normal or revote) is allowed.
     */
    public function canVote(): bool
    {
        return  $this->is_active ||
                $this->is_for_revote ||
                $this->isRevoteActive();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRevote($query)
    {
        return $query->where('is_for_revote', true);
    }
}

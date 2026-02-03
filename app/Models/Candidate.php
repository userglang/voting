<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Candidate extends Model
{
    use HasFactory;

    // The table associated with the model.
    protected $table = 'candidates';

    // Indicate that the 'id' column is a UUID
    protected $keyType = 'string';

    // Disable auto-increment for the primary key (since we are using UUID)
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'position_id',
        'first_name',
        'last_name',
        'middle_name',
        'background_profile',
        'image',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',         // Cast the UUID to a string
        'position_id' => 'string', // Cast position_id to string if it's a UUID
    ];

    /**
     * The candidate belongs to a position.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Automatically generate a UUID for the candidate if not provided.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();  // Generate UUID if not provided
            }
        });
    }

    /**
     * Accessor for the candidate's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name} {$this->middle_name}";
    }

    /**
     * Accessor to get the profile image URL.
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        // Assuming you're using the public storage disk
        return $this->image ? asset("storage/{$this->image}") : null;
    }

    /**
     * Accessor to get the background profile (with a default message if null).
     *
     * @return string
     */
    public function getBackgroundProfileAttribute(): string
    {
        return $this->attributes['background_profile'] ?: 'No background profile provided';
    }
}

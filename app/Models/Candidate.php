<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function votes()
    {
        return $this->hasMany(Vote::class);
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
     * Mutator: When saving image to database
     * Store only the filename, not the full path
     *
     * @param mixed $value
     * @return void
     */
    public function setImageAttribute($value): void
    {
        if ($value && is_string($value)) {
            // If it's a full path, extract just the filename
            if (str_contains($value, 'candidates/images/')) {
                $this->attributes['image'] = basename($value);
            }
            // If it's already just a filename, use as-is
            elseif (str_contains($value, '/')) {
                // If it has other directory structures, get just the filename
                $this->attributes['image'] = basename($value);
            }
            else {
                $this->attributes['image'] = $value;
            }
        } else {
            $this->attributes['image'] = $value;
        }
    }

    /**
     * Accessor to get the full image path for storage
     * This returns the path relative to storage/app/public
     *
     * @return string|null
     */
    public function getImagePathAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return 'candidates/images/' . $this->image;
    }

    /**
     * Accessor to get the profile image URL for web display.
     * This returns the public URL that can be used in <img> tags
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return Storage::url('candidates/images/' . $this->image);
    }

    /**
     * Accessor to get base64 encoded image for Filament ImageColumn
     *
     * @return string|null
     */
    public function getImageBase64Attribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        $path = storage_path('app/public/candidates/images/' . $this->image);

        if (!file_exists($path)) {
            Log::warning('Candidate image file not found', [
                'candidate_id' => $this->id,
                'path' => $path
            ]);
            return null;
        }

        try {
            $mimeType = mime_content_type($path);
            $data = file_get_contents($path);
            return 'data:' . $mimeType . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            Log::error('Failed to encode candidate image', [
                'candidate_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if the image file exists in storage
     *
     * @return bool
     */
    public function getImageExistsAttribute(): bool
    {
        if (!$this->image) {
            return false;
        }

        return Storage::disk('public')->exists('candidates/images/' . $this->image);
    }

    /**
     * Get the image file size
     *
     * @return int|null
     */
    public function getImageSizeAttribute(): ?int
    {
        if (!$this->image || !$this->image_exists) {
            return null;
        }

        return Storage::disk('public')->size('candidates/images/' . $this->image);
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

    /**
     * Delete the associated image file when candidate is deleted
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleting(function ($candidate) {
            if ($candidate->image && $candidate->image_exists) {
                Storage::disk('public')->delete('candidates/images/' . $candidate->image);
            }
        });
    }
}

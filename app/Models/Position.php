<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Position extends Model
{
    //

    use HasFactory;

    // The table associated with the model.
    protected $table = 'positions';

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
        'id',
        'title',
        'vacant_count',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'vacant_count' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Auto-generate UUID for the primary key.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}

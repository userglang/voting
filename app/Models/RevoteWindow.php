<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class RevoteWindow extends Model
{
    use HasUuids;

    protected $table = 'revote_windows';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';



    protected $fillable = [
        'position_id',
        'reason',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


    /**
     * A revote window belongs to a position.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Check if revote window is currently active.
     */
    public function isActive(): bool
    {
        return now()->between($this->start_at, $this->end_at);
    }
}

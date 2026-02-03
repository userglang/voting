<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    //
    use HasFactory, HasUuids;

    protected $table = 'branches';

    // Primary key is UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'branch_number',
        'branch_name',
        'address',
        'email',
        'contact_person',
        'contact_number',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Automatically generate a UUID when creating a new Branch.
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

    public function members()
    {
        return $this->hasMany(Member::class, 'branch_number');
    }


}

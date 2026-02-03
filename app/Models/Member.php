<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Member extends Model
{
    //

    use HasFactory;

    // The table associated with the model.
    protected $table = 'members';

    // Indicate that the 'id' column is a UUID
    protected $keyType = 'string';

    // Disable auto-increment for the primary key (since we are using UUID)
    public $incrementing = false;

    // Mass assignable attributes (for bulk insert, updates)
    protected $fillable = [
        'code',
        'cid',
        'branch_number',
        'first_name',
        'last_name',
        'middle_name',
        'address',
        'occupation',
        'birth_date',
        'email',
        'contact_number',
        'gender',
        'marital_status',
        'religion',
        'share_account',
        'is_migs',
        'share_amount',
        'is_active',
        'is_registered',
        'process_type',
        'registration_type',
        'membership_date',
    ];

    // Attributes that should be cast to native types (useful for dates, booleans, etc.)
    protected $casts = [
        'birth_date' => 'date',
        'membership_date' => 'date',
        'is_migs' => 'boolean',
        'is_active' => 'boolean',
        'is_registered' => 'boolean',
    ];

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

    // Defining the relationship with the Branch model (assuming you have a Branch model)
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_number', 'branch_number');
    }


    /**
     * Accessor: Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    /**
     * Accessor: Get formatted birth date.
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        return $this->birth_date?->format('F d, Y');
    }

    /**
     * Accessor: Get age from birth_date.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'license_number',
        'hire_date',
        'status',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the driver.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the trips for the driver.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}

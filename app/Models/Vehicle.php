<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'brand',
        'model',
        'year',
        'color',
        'plate_number',
        'capacity',
        'status',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the vehicle.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the trips for the vehicle.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}

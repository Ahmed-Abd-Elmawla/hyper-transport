<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'driver_id',
        'vehicle_id',
        'destination',
        'start_at',
        'end_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Get the company that owns the trip.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the driver that owns the trip.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle that owns the trip.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

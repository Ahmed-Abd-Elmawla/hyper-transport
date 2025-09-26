<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    /**
     * Get available drivers for a company within a specific time period
     */
    public static function getAvailableDrivers(
        int $companyId,
        Carbon $startAt,
        ?Carbon $endAt = null,
        ?int $excludeTripId = null
    ): Collection {
        return Driver::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereDoesntHave('trips', function (Builder $query) use ($startAt, $endAt, $excludeTripId) {
                $query->whereIn('status', ['scheduled', 'in_progress']);

                if ($excludeTripId) {
                    $query->where('id', '!=', $excludeTripId);
                }

                $query->where(function (Builder $q) use ($startAt, $endAt) {
                    if ($endAt) {
                        // Check for overlapping trips when end_at is provided
                        $q->where(function (Builder $subQ) use ($startAt, $endAt) {
                            $subQ->where('start_at', '<', $endAt)
                                ->where(function (Builder $endQ) use ($startAt) {
                                    $endQ->whereNull('end_at')
                                        ->orWhere('end_at', '>', $startAt);
                                });
                        });
                    } else {
                        // When no end_at is provided, check for conflicts with start time
                        $q->where(function (Builder $subQ) use ($startAt) {
                            $subQ->where('start_at', '<=', $startAt)
                                ->where(function (Builder $endQ) use ($startAt) {
                                    $endQ->whereNull('end_at')
                                        ->orWhere('end_at', '>', $startAt);
                                });
                        });
                    }
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available vehicles for a company within a specific time period
     */
    public static function getAvailableVehicles(
        int $companyId,
        Carbon $startAt,
        ?Carbon $endAt = null,
        ?int $excludeTripId = null
    ): Collection {
        return Vehicle::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereDoesntHave('trips', function (Builder $query) use ($startAt, $endAt, $excludeTripId) {
                $query->whereIn('status', ['scheduled', 'in_progress']);

                if ($excludeTripId) {
                    $query->where('id', '!=', $excludeTripId);
                }

                $query->where(function (Builder $q) use ($startAt, $endAt) {
                    if ($endAt) {
                        // Check for overlapping trips when end_at is provided
                        $q->where(function (Builder $subQ) use ($startAt, $endAt) {
                            $subQ->where('start_at', '<', $endAt)
                                ->where(function (Builder $endQ) use ($startAt) {
                                    $endQ->whereNull('end_at')
                                        ->orWhere('end_at', '>', $startAt);
                                });
                        });
                    } else {
                        // When no end_at is provided, check for conflicts with start time
                        $q->where(function (Builder $subQ) use ($startAt) {
                            $subQ->where('start_at', '<=', $startAt)
                                ->where(function (Builder $endQ) use ($startAt) {
                                    $endQ->whereNull('end_at')
                                        ->orWhere('end_at', '>', $startAt);
                                });
                        });
                    }
                });
            })
            ->orderBy('brand')
            ->orderBy('model')
            ->get();
    }

    /**
     * Check if a specific driver is available during a time period
     */
    public static function isDriverAvailable(
        int $driverId,
        Carbon $startAt,
        ?Carbon $endAt = null,
        ?int $excludeTripId = null
    ): bool {
        $driver = Driver::find($driverId);

        if (!$driver || !$driver->is_active || $driver->status !== 'active') {
            return false;
        }

        return !Trip::where('driver_id', $driverId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($excludeTripId, fn($query) => $query->where('id', '!=', $excludeTripId))
            ->where(function (Builder $query) use ($startAt, $endAt) {
                if ($endAt) {
                    $query->where(function (Builder $q) use ($startAt, $endAt) {
                        $q->where('start_at', '<', $endAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                } else {
                    $query->where(function (Builder $q) use ($startAt) {
                        $q->where('start_at', '<=', $startAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                }
            })
            ->exists();
    }

    /**
     * Check if a specific vehicle is available during a time period
     */
    public static function isVehicleAvailable(
        int $vehicleId,
        Carbon $startAt,
        ?Carbon $endAt = null,
        ?int $excludeTripId = null
    ): bool {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle || !$vehicle->is_active || $vehicle->status !== 'available') {
            return false;
        }

        return !Trip::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($excludeTripId, fn($query) => $query->where('id', '!=', $excludeTripId))
            ->where(function (Builder $query) use ($startAt, $endAt) {
                if ($endAt) {
                    $query->where(function (Builder $q) use ($startAt, $endAt) {
                        $q->where('start_at', '<', $endAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                } else {
                    $query->where(function (Builder $q) use ($startAt) {
                        $q->where('start_at', '<=', $startAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                }
            })
            ->exists();
    }

    /**
     * Get availability summary for a company within a time period
     */
    public static function getAvailabilitySummary(
        int $companyId,
        Carbon $startAt,
        ?Carbon $endAt = null
    ): array {
        $availableDrivers = static::getAvailableDrivers($companyId, $startAt, $endAt);
        $availableVehicles = static::getAvailableVehicles($companyId, $startAt, $endAt);

        $totalDrivers = Driver::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->count();

        $totalVehicles = Vehicle::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', 'available')
            ->count();

        return [
            'drivers' => [
                'available' => $availableDrivers->count(),
                'total' => $totalDrivers,
                'percentage' => $totalDrivers > 0 ? round(($availableDrivers->count() / $totalDrivers) * 100, 1) : 0,
                'list' => $availableDrivers,
            ],
            'vehicles' => [
                'available' => $availableVehicles->count(),
                'total' => $totalVehicles,
                'percentage' => $totalVehicles > 0 ? round(($availableVehicles->count() / $totalVehicles) * 100, 1) : 0,
                'list' => $availableVehicles,
            ],
        ];
    }

    /**
     * Get conflicting trips for a time period
     */
    public static function getConflictingTrips(
        int $companyId,
        Carbon $startAt,
        ?Carbon $endAt = null
    ): Collection {
        return Trip::with(['driver', 'vehicle'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function (Builder $query) use ($startAt, $endAt) {
                if ($endAt) {
                    $query->where(function (Builder $q) use ($startAt, $endAt) {
                        $q->where('start_at', '<', $endAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                } else {
                    $query->where(function (Builder $q) use ($startAt) {
                        $q->where('start_at', '<=', $startAt)
                            ->where(function (Builder $endQ) use ($startAt) {
                                $endQ->whereNull('end_at')
                                    ->orWhere('end_at', '>', $startAt);
                            });
                    });
                }
            })
            ->orderBy('start_at')
            ->get();
    }
}

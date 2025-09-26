<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\AvailabilityService;
use Carbon\Carbon;

describe('AvailabilityService', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->driver = Driver::factory()->for($this->company)->create([
            'is_active' => true,
            'status' => 'active'
        ]);
        $this->vehicle = Vehicle::factory()->for($this->company)->create([
            'is_active' => true,
            'status' => 'available'
        ]);
    });

    describe('getAvailableDrivers', function () {
        it('returns available drivers when no conflicts exist', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $availableDrivers = AvailabilityService::getAvailableDrivers(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableDrivers)->toHaveCount(1)
                ->and($availableDrivers->first()->id)->toBe($this->driver->id);
        });

        it('excludes drivers with conflicting trips', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            // Create conflicting trip
            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $startAt->copy()->subMinutes(30),
                'end_at' => $startAt->copy()->addMinutes(30),
                'status' => 'scheduled'
            ]);

            $availableDrivers = AvailabilityService::getAvailableDrivers(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableDrivers)->toHaveCount(0);
        });

        it('excludes inactive drivers', function () {
            $this->driver->update(['is_active' => false]);

            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $availableDrivers = AvailabilityService::getAvailableDrivers(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableDrivers)->toHaveCount(0);
        });

        it('excludes drivers with non-active status', function () {
            $this->driver->update(['status' => 'inactive']);

            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $availableDrivers = AvailabilityService::getAvailableDrivers(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableDrivers)->toHaveCount(0);
        });
    });

    describe('getAvailableVehicles', function () {
        it('returns available vehicles when no conflicts exist', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $availableVehicles = AvailabilityService::getAvailableVehicles(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableVehicles)->toHaveCount(1)
                ->and($availableVehicles->first()->id)->toBe($this->vehicle->id);
        });

        it('excludes vehicles with conflicting trips', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            // Create conflicting trip
            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $startAt->copy()->subMinutes(30),
                'end_at' => $startAt->copy()->addMinutes(30),
                'status' => 'scheduled'
            ]);

            $availableVehicles = AvailabilityService::getAvailableVehicles(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($availableVehicles)->toHaveCount(0);
        });
    });

    describe('isDriverAvailable', function () {
        it('returns true for available driver', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $isAvailable = AvailabilityService::isDriverAvailable(
                $this->driver->id,
                $startAt,
                $endAt
            );

            expect($isAvailable)->toBeTrue();
        });

        it('returns false for driver with conflicting trip', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $startAt->copy()->subMinutes(30),
                'end_at' => $startAt->copy()->addMinutes(30),
                'status' => 'scheduled'
            ]);

            $isAvailable = AvailabilityService::isDriverAvailable(
                $this->driver->id,
                $startAt,
                $endAt
            );

            expect($isAvailable)->toBeFalse();
        });

        it('returns false for non-existent driver', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $isAvailable = AvailabilityService::isDriverAvailable(
                999999,
                $startAt,
                $endAt
            );

            expect($isAvailable)->toBeFalse();
        });
    });

    describe('isVehicleAvailable', function () {
        it('returns true for available vehicle', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $isAvailable = AvailabilityService::isVehicleAvailable(
                $this->vehicle->id,
                $startAt,
                $endAt
            );

            expect($isAvailable)->toBeTrue();
        });

        it('returns false for vehicle with conflicting trip', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $startAt->copy()->subMinutes(30),
                'end_at' => $startAt->copy()->addMinutes(30),
                'status' => 'scheduled'
            ]);

            $isAvailable = AvailabilityService::isVehicleAvailable(
                $this->vehicle->id,
                $startAt,
                $endAt
            );

            expect($isAvailable)->toBeFalse();
        });
    });

    describe('getAvailabilitySummary', function () {
        it('returns correct summary with available resources', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $summary = AvailabilityService::getAvailabilitySummary(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($summary)->toHaveKeys(['drivers', 'vehicles'])
                ->and($summary['drivers'])->toHaveKeys(['available', 'total', 'percentage', 'list'])
                ->and($summary['vehicles'])->toHaveKeys(['available', 'total', 'percentage', 'list'])
                ->and($summary['drivers']['available'])->toBe(1)
                ->and($summary['vehicles']['available'])->toBe(1)
                ->and($summary['drivers']['list'])->toHaveCount(1)
                ->and($summary['vehicles']['list'])->toHaveCount(1);
        });
    });

    describe('getConflictingTrips', function () {
        it('returns conflicting trips within time period', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $conflictingTrip = Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $startAt->copy()->subMinutes(30),
                'end_at' => $startAt->copy()->addMinutes(30),
                'status' => 'scheduled'
            ]);

            $conflicts = AvailabilityService::getConflictingTrips(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($conflicts)->toHaveCount(1)
                ->and($conflicts->first()->id)->toBe($conflictingTrip->id);
        });

        it('does not return non-conflicting trips', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            // Create non-conflicting trip (after our time period)
            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'start_at' => $endAt->copy()->addHour(),
                'end_at' => $endAt->copy()->addHours(2),
                'status' => 'scheduled'
            ]);

            $conflicts = AvailabilityService::getConflictingTrips(
                $this->company->id,
                $startAt,
                $endAt
            );

            expect($conflicts)->toHaveCount(0);
        });
    });
});
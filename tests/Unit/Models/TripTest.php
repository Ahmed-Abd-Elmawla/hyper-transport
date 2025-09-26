<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;

describe('Trip Model', function () {
    it('can be created with valid attributes', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create();
        $vehicle = Vehicle::factory()->for($company)->create();

        $startAt = Carbon::now()->addHour();
        $endAt = Carbon::now()->addHours(3);

        $trip = Trip::factory()->for($company)->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'destination' => '123 Main St',
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => 'scheduled',
            'notes' => 'Test trip',
        ]);

        expect($trip->destination)->toBe('123 Main St')
            ->and($trip->status)->toBe('scheduled')
            ->and($trip->notes)->toBe('Test trip')
            ->and($trip->start_at)->toBeInstanceOf(Carbon::class)
            ->and($trip->end_at)->toBeInstanceOf(Carbon::class);
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $trip = Trip::factory()->for($company)->create();

        expect($trip->company)->toBeInstanceOf(Company::class)
            ->and($trip->company->id)->toBe($company->id);
    });

    it('belongs to a driver', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create();
        $trip = Trip::factory()->for($company)->create(['driver_id' => $driver->id]);

        expect($trip->driver)->toBeInstanceOf(Driver::class)
            ->and($trip->driver->id)->toBe($driver->id);
    });

    it('belongs to a vehicle', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->for($company)->create();
        $trip = Trip::factory()->for($company)->create(['vehicle_id' => $vehicle->id]);

        expect($trip->vehicle)->toBeInstanceOf(Vehicle::class)
            ->and($trip->vehicle->id)->toBe($vehicle->id);
    });

    it('casts start_at and end_at to datetime', function () {
        $trip = Trip::factory()->create([
            'start_at' => '2023-01-15 10:00:00',
            'end_at' => '2023-01-15 12:00:00',
        ]);

        expect($trip->start_at)->toBeInstanceOf(Carbon::class)
            ->and($trip->end_at)->toBeInstanceOf(Carbon::class);
    });
});
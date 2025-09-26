<?php

use App\Models\Company;
use App\Models\Vehicle;
use App\Models\Trip;

describe('Vehicle Model', function () {
    it('can be created with valid attributes', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->for($company)->create([
            'brand' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'color' => '#FF0000',
            'plate_number' => 'ABC-1234',
            'capacity' => 5,
            'status' => 'available',
            'is_active' => true,
        ]);

        expect($vehicle->brand)->toBe('Toyota')
            ->and($vehicle->model)->toBe('Camry')
            ->and($vehicle->year)->toBe(2022)
            ->and($vehicle->color)->toBe('#FF0000')
            ->and($vehicle->plate_number)->toBe('ABC-1234')
            ->and($vehicle->capacity)->toBe(5)
            ->and($vehicle->status)->toBe('available')
            ->and($vehicle->is_active)->toBeTrue();
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->for($company)->create();

        expect($vehicle->company)->toBeInstanceOf(Company::class)
            ->and($vehicle->company->id)->toBe($company->id);
    });

    it('has many trips relationship', function () {
        $company = Company::factory()->create();
        $driver = \App\Models\Driver::factory()->for($company)->create();
        $vehicle = Vehicle::factory()->for($company)->create();
        $trips = Trip::factory()->count(2)->for($company)->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($vehicle->trips)->toHaveCount(2)
            ->and($vehicle->trips->first())->toBeInstanceOf(Trip::class);
    });
});
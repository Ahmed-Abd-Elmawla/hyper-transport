<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;

describe('Driver Model', function () {
    it('can be created with valid attributes', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'license_number' => 'LIC-1234-AB',
            'status' => 'active',
            'is_active' => true,
        ]);

        expect($driver->name)->toBe('John Doe')
            ->and($driver->email)->toBe('john@example.com')
            ->and($driver->phone)->toBe('123-456-7890')
            ->and($driver->license_number)->toBe('LIC-1234-AB')
            ->and($driver->status)->toBe('active')
            ->and($driver->is_active)->toBeTrue();
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create();

        expect($driver->company)->toBeInstanceOf(Company::class)
            ->and($driver->company->id)->toBe($company->id);
    });

    it('has many trips relationship', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create();
        $vehicle = \App\Models\Vehicle::factory()->for($company)->create();
        $trips = Trip::factory()->count(2)->for($company)->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($driver->trips)->toHaveCount(2)
            ->and($driver->trips->first())->toBeInstanceOf(Trip::class);
    });

    it('casts hire_date to date', function () {
        $driver = Driver::factory()->create([
            'hire_date' => '2023-01-15'
        ]);

        expect($driver->hire_date)->toBeInstanceOf(\Carbon\Carbon::class);
    });
});
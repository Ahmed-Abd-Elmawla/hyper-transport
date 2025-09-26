<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;

describe('Company Model', function () {
    it('can be created with valid attributes', function () {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'phone' => '123-456-7890',
            'address' => '123 Test St'
        ]);

        expect($company->name)->toBe('Test Company')
            ->and($company->email)->toBe('test@company.com')
            ->and($company->phone)->toBe('123-456-7890')
            ->and($company->address)->toBe('123 Test St');
    });

    it('has many drivers relationship', function () {
        $company = Company::factory()->create();
        $drivers = Driver::factory()->count(3)->for($company)->create();

        expect($company->drivers)->toHaveCount(3)
            ->and($company->drivers->first())->toBeInstanceOf(Driver::class);
    });

    it('has many vehicles relationship', function () {
        $company = Company::factory()->create();
        $vehicles = Vehicle::factory()->count(2)->for($company)->create();

        expect($company->vehicles)->toHaveCount(2)
            ->and($company->vehicles->first())->toBeInstanceOf(Vehicle::class);
    });

    it('has many trips relationship', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->for($company)->create();
        $vehicle = Vehicle::factory()->for($company)->create();
        $trips = Trip::factory()->count(3)->for($company)->create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($company->trips)->toHaveCount(3)
            ->and($company->trips->first())->toBeInstanceOf(Trip::class);
    });
});
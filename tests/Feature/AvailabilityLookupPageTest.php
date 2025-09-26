<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

describe('AvailabilityLookup Page', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
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

    it('can render the availability lookup page', function () {
        $this->actingAs($this->user);

        Livewire::test(\App\Filament\Pages\AvailabilityLookup::class)
            ->assertOk()
            ->assertSee('Availability Lookup');
    });

    it('shows available drivers and vehicles when no conflicts exist', function () {
        $this->actingAs($this->user);

        $startAt = Carbon::now()->addHour();
        $endAt = Carbon::now()->addHours(3);

        Livewire::test(\App\Filament\Pages\AvailabilityLookup::class)
            ->set('company_id', $this->company->id)
            ->set('start_at', $startAt->format('Y-m-d H:i'))
            ->set('end_at', $endAt->format('Y-m-d H:i'))
            ->call('recalculate')
            ->assertSet('availableDrivers', function ($drivers) {
                return count($drivers) === 1 && $drivers[0]['name'] === $this->driver->name;
            })
            ->assertSet('availableVehicles', function ($vehicles) {
                return count($vehicles) === 1 && $vehicles[0]['brand'] === $this->vehicle->brand;
            });
    });

    it('hides drivers and vehicles with conflicting trips', function () {
        $this->actingAs($this->user);

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

        Livewire::test(\App\Filament\Pages\AvailabilityLookup::class)
            ->set('company_id', $this->company->id)
            ->set('start_at', $startAt->format('Y-m-d H:i'))
            ->set('end_at', $endAt->format('Y-m-d H:i'))
            ->call('recalculate')
            ->assertSet('availableDrivers', [])
            ->assertSet('availableVehicles', []);
    });

    it('can search for all companies', function () {
        $this->actingAs($this->user);

        $company2 = Company::factory()->create();
        $driver2 = Driver::factory()->for($company2)->create([
            'is_active' => true,
            'status' => 'active'
        ]);

        $startAt = Carbon::now()->addHour();
        $endAt = Carbon::now()->addHours(3);

        Livewire::test(\App\Filament\Pages\AvailabilityLookup::class)
            ->set('company_id', 0) // All companies
            ->set('start_at', $startAt->format('Y-m-d H:i'))
            ->set('end_at', $endAt->format('Y-m-d H:i'))
            ->call('recalculate')
            ->assertSet('availableDrivers', function ($drivers) use ($driver2) {
                return count($drivers) === 2 &&
                       collect($drivers)->pluck('name')->contains($this->driver->name) &&
                       collect($drivers)->pluck('name')->contains($driver2->name);
            });
    });
});
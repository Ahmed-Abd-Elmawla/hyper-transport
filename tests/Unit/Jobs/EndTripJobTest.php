<?php

use App\Jobs\EndTripJob;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;

describe('EndTripJob', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->driver = Driver::factory()->for($this->company)->create([
            'status' => 'inactive',
            'is_active' => false,
        ]);
        $this->vehicle = Vehicle::factory()->for($this->company)->create([
            'status' => 'in_use',
        ]);
        $this->trip = Trip::factory()->for($this->company)->create([
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'in_progress',
            'start_at' => Carbon::now()->subHour(),
            'end_at' => Carbon::now(),
        ]);
    });

    it('can be instantiated with trip ID and scheduled end time', function () {
        $scheduledEndAt = Carbon::now();
        $job = new EndTripJob($this->trip->id, $scheduledEndAt);

        expect($job->tripId)->toBe($this->trip->id)
            ->and($job->scheduledEndAt)->toEqual($scheduledEndAt);
    });

    it('updates trip status to completed when executed', function () {
        $job = new EndTripJob($this->trip->id, $this->trip->end_at);
        
        expect($this->trip->fresh()->status)->toBe('in_progress');
        
        $job->handle();
        
        expect($this->trip->fresh()->status)->toBe('completed');
    });

    it('does not update non-existent trip', function () {
        $job = new EndTripJob(999999, Carbon::now());
        
        // Should not throw any exception
        $job->handle();
        
        expect(true)->toBeTrue(); // Test passes if no exception is thrown
    });

    it('does not update trip that is already completed', function () {
        $this->trip->update(['status' => 'completed']);
        
        $job = new EndTripJob($this->trip->id, $this->trip->end_at);
        $job->handle();
        
        expect($this->trip->fresh()->status)->toBe('completed');
    });

    it('updates driver and vehicle status when trip ends', function () {
        $job = new EndTripJob($this->trip->id, $this->trip->end_at);
        
        expect($this->driver->fresh()->status)->toBe('inactive')
            ->and($this->driver->fresh()->is_active)->toBeFalse()
            ->and($this->vehicle->fresh()->status)->toBe('in_use');
        
        $job->handle();
        
        expect($this->driver->fresh()->status)->toBe('active')
            ->and($this->driver->fresh()->is_active)->toBeTrue()
            ->and($this->vehicle->fresh()->status)->toBe('available');
    });

    it('skips execution if end time changed after scheduling', function () {
        $originalEndAt = $this->trip->end_at;
        $newEndAt = $originalEndAt->copy()->addHour();
        
        // Update trip end time
        $this->trip->update(['end_at' => $newEndAt]);
        
        // Job still has old end time
        $job = new EndTripJob($this->trip->id, $originalEndAt);
        $job->handle();
        
        // Trip should remain in_progress since times don't match
        expect($this->trip->fresh()->status)->toBe('in_progress');
    });
});
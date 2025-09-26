<?php

use App\Jobs\StartTripJob;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Carbon\Carbon;

describe('StartTripJob', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->driver = Driver::factory()->for($this->company)->create();
        $this->vehicle = Vehicle::factory()->for($this->company)->create();
        $this->trip = Trip::factory()->for($this->company)->create([
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'scheduled',
            'start_at' => Carbon::now()->addHour(),
        ]);
    });

    it('can be instantiated with trip ID and scheduled start time', function () {
        $scheduledStartAt = Carbon::now()->addHour();
        $job = new StartTripJob($this->trip->id, $scheduledStartAt);

        expect($job->tripId)->toBe($this->trip->id)
            ->and($job->scheduledStartAt)->toEqual($scheduledStartAt);
    });

    it('updates trip status to in_progress when executed', function () {
        $job = new StartTripJob($this->trip->id, $this->trip->start_at);

        expect($this->trip->fresh()->status)->toBe('scheduled');

        $job->handle();

        expect($this->trip->fresh()->status)->toBe('in_progress');
    });

    it('does not update non-existent trip', function () {
        $job = new StartTripJob(999999, Carbon::now());

        // Should not throw any exception
        $job->handle();

        expect(true)->toBeTrue(); // Test passes if no exception is thrown
    });

    it('does not update trip that is not scheduled', function () {
        $this->trip->update(['status' => 'completed']);

        $job = new StartTripJob($this->trip->id, $this->trip->start_at);
        $job->handle();

        expect($this->trip->fresh()->status)->toBe('completed');
    });

    it('updates driver and vehicle status when trip starts', function () {
        // Set initial status to what we expect before the job runs
        $this->driver->update(['status' => 'active', 'is_active' => true]);
        $this->vehicle->update(['status' => 'available']);

        $job = new StartTripJob($this->trip->id, $this->trip->start_at);

        expect($this->driver->fresh()->status)->toBe('active')
            ->and($this->driver->fresh()->is_active)->toBeTrue()
            ->and($this->vehicle->fresh()->status)->toBe('available');

        $job->handle();

        expect($this->driver->fresh()->status)->toBe('inactive')
            ->and($this->driver->fresh()->is_active)->toBeFalse()
            ->and($this->vehicle->fresh()->status)->toBe('in_use');
    });

    it('skips execution if start time changed after scheduling', function () {
        $originalStartAt = $this->trip->start_at;
        $newStartAt = $originalStartAt->copy()->addHour();

        // Update trip start time
        $this->trip->update(['start_at' => $newStartAt]);

        // Job still has old start time
        $job = new StartTripJob($this->trip->id, $originalStartAt);
        $job->handle();

        // Trip should remain scheduled since times don't match
        expect($this->trip->fresh()->status)->toBe('scheduled');
    });
});

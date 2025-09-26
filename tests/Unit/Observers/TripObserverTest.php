<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Observers\TripObserver;
use App\Jobs\StartTripJob;
use App\Jobs\EndTripJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

describe('TripObserver', function () {
    beforeEach(function () {
        Bus::fake();

        $this->company = Company::factory()->create();
        $this->driver = Driver::factory()->for($this->company)->create();
        $this->vehicle = Vehicle::factory()->for($this->company)->create();
    });

    describe('created', function () {
        it('schedules jobs when trip is created with scheduled status', function () {
            $startAt = Carbon::now()->addHour();
            $endAt = Carbon::now()->addHours(3);

            $trip = Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'scheduled',
                'start_at' => $startAt,
                'end_at' => $endAt,
            ]);

            Bus::assertBatched(function ($batch) use ($trip) {
                return $batch->jobs->count() === 2 &&
                       $batch->jobs->contains(fn($job) => $job instanceof StartTripJob && $job->tripId === $trip->id) &&
                       $batch->jobs->contains(fn($job) => $job instanceof EndTripJob && $job->tripId === $trip->id);
            });
        });

        it('does not schedule jobs when trip is not scheduled', function () {
            Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'completed',
                'start_at' => Carbon::now()->subHour(),
                'end_at' => Carbon::now(),
            ]);

            Bus::assertNothingBatched();
        });

        it('schedules only start job when end_at is null', function () {
            $trip = Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'scheduled',
                'start_at' => Carbon::now()->addHour(),
                'end_at' => null,
            ]);

            Bus::assertBatched(function ($batch) use ($trip) {
                return $batch->jobs->count() === 1 &&
                       $batch->jobs->contains(fn($job) => $job instanceof StartTripJob && $job->tripId === $trip->id);
            });
        });
    });

    describe('updated', function () {
        it('cleans up jobs when trip status changes from scheduled', function () {
            // Create trip without triggering observer initially
            $trip = new Trip([
                'company_id' => $this->company->id,
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'scheduled',
                'start_at' => Carbon::now()->addHour(),
                'end_at' => Carbon::now()->addHours(3),
                'job_batch_id' => 'test-batch-id',
                'destination' => 'Test destination',
            ]);
            $trip->saveQuietly(); // Save without triggering observers

            // Mock the cleanup service
            $mock = $this->mock(\App\Services\QueueCleanupService::class);
            $mock->shouldReceive('deleteBatchCompletely')
                ->once()
                ->with('test-batch-id')
                ->andReturn(true);
            $mock->shouldReceive('deletePendingTripJobs')
                ->once()
                ->with($trip->id)
                ->andReturn(2);

            // Now update the trip (this will trigger the observer)
            $trip->update(['status' => 'completed']);
        });

        it('reschedules jobs when scheduled trip is updated', function () {
            // Create trip that will trigger observer on creation
            $trip = Trip::factory()->for($this->company)->create([
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'scheduled',
                'start_at' => Carbon::now()->addHour(),
                'end_at' => Carbon::now()->addHours(3),
            ]);

            // Clear the bus fake to reset previous batches
            Bus::fake();

            // Update the trip (should trigger rescheduling)
            $trip->update(['start_at' => Carbon::now()->addHours(2)]);

            Bus::assertBatched(function ($batch) use ($trip) {
                return $batch->jobs->count() === 2;
            });
        });
    });

    describe('deleted', function () {
        it('cleans up jobs when trip is deleted', function () {
            // Create trip without triggering observer
            $trip = new Trip([
                'company_id' => $this->company->id,
                'driver_id' => $this->driver->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => 'scheduled',
                'start_at' => Carbon::now()->addHour(),
                'end_at' => Carbon::now()->addHours(3),
                'job_batch_id' => 'test-batch-id',
                'destination' => 'Test destination',
            ]);
            $trip->saveQuietly();

            // Mock the cleanup service
            $mock = $this->mock(\App\Services\QueueCleanupService::class);
            $mock->shouldReceive('deleteBatchCompletely')
                ->once()
                ->with('test-batch-id')
                ->andReturn(true);
            $mock->shouldReceive('deletePendingTripJobs')
                ->once()
                ->with($trip->id)
                ->andReturn(1);

            $trip->delete();
        });
    });
});

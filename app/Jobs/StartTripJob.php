<?php

namespace App\Jobs;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartTripJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use \Illuminate\Bus\Batchable;

    public function __construct(public int $tripId, public ?\Carbon\Carbon $scheduledStartAt)
    {
        //
    }

    public function handle(): void
    {
        // Skip if the batch was cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        $trip = Trip::with(['driver', 'vehicle'])->find($this->tripId);

        if (! $trip) {
            return;
        }

        // Only transition from scheduled -> in_progress
        if ($trip->status !== 'scheduled') {
            return;
        }

        // If the trip start time changed after scheduling, skip (old job)
        if ($this->scheduledStartAt && $trip->start_at && ! $trip->start_at->equalTo($this->scheduledStartAt)) {
            return;
        }

        $trip->status = 'in_progress';
        $trip->save();

        if ($trip->driver) {
            $trip->driver->status = 'inactive';
            $trip->driver->is_active = false;
            $trip->driver->save();
        }

        if ($trip->vehicle) {
            $trip->vehicle->status = 'in_use';
            $trip->vehicle->save();
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EndTripJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use \Illuminate\Bus\Batchable;

    public function __construct(public int $tripId, public ?\Carbon\Carbon $scheduledEndAt)
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

        // Only transition to completed if currently in_progress or scheduled
        if (! in_array($trip->status, ['in_progress', 'scheduled'], true)) {
            return;
        }

        // If the trip end time changed after scheduling, skip (old job)
        if ($this->scheduledEndAt && $trip->end_at && ! $trip->end_at->equalTo($this->scheduledEndAt)) {
            return;
        }

        $trip->status = 'completed';
        $trip->save();

        if ($trip->driver) {
            $trip->driver->status = 'active';
            $trip->driver->is_active = true;
            $trip->driver->save();
        }

        if ($trip->vehicle) {
            $trip->vehicle->status = 'available';
            $trip->vehicle->save();
        }
    }
}

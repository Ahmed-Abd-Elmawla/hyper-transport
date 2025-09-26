<?php

namespace App\Observers;

use App\Models\Trip;
use App\Jobs\StartTripJob;
use App\Jobs\EndTripJob;
use Illuminate\Support\Facades\Bus;
use App\Services\QueueCleanupService;
use Illuminate\Support\Facades\Log;

class TripObserver
{
    public function created(Trip $trip): void
    {
        if ($trip->status !== 'scheduled') {
            return;
        }

        $this->scheduleJobsForTrip($trip);
    }

    public function updated(Trip $trip): void
    {
        // If trip moved away from 'scheduled', clean up any pending jobs
        if ($trip->status !== 'scheduled') {
            $this->cleanupTripJobs($trip);
            $trip->job_batch_id = null;
            $trip->saveQuietly();
            return;
        }

        // If schedule or assignments changed, clean up old jobs and create new ones
        if (
            $trip->wasChanged('start_at') ||
            $trip->wasChanged('end_at') ||
            $trip->wasChanged('driver_id') ||
            $trip->wasChanged('vehicle_id')
        ) {
            Log::info("Trip {$trip->id} updated, deleting old batch and creating new jobs");
            $this->cleanupTripJobs($trip);
            $this->scheduleJobsForTrip($trip);
        }
    }

    public function deleted(Trip $trip): void
    {
        $this->cleanupTripJobs($trip);
    }

    private function cleanupTripJobs(Trip $trip): void
    {
        $queueCleanupService = app(QueueCleanupService::class);

        // Completely delete the batch and all its jobs if we have a batch ID
        if ($trip->job_batch_id) {
            $queueCleanupService->deleteBatchCompletely($trip->job_batch_id);
        }

        // Also delete pending jobs directly from the queue table as a backup
        $deletedCount = $queueCleanupService->deletePendingTripJobs($trip->id);

        if ($deletedCount > 0) {
            Log::info("Deleted {$deletedCount} additional pending jobs for trip {$trip->id}");
        }
    }

    private function scheduleJobsForTrip(Trip $trip): void
    {
        $jobs = [];

        // Create start job
        if ($trip->start_at) {
            $startJob = new StartTripJob($trip->id, $trip->start_at);
            $startJob->delay($trip->start_at->isFuture() ? $trip->start_at : now());
            $jobs[] = $startJob;
        }

        // Create end job
        if ($trip->end_at) {
            $endJob = new EndTripJob($trip->id, $trip->end_at);
            $endJob->delay($trip->end_at->isFuture() ? $trip->end_at : now());
            $jobs[] = $endJob;
        }

        if (!empty($jobs)) {
            // Dispatch as a batch
            $batch = Bus::batch($jobs)
                ->name("Trip {$trip->id} jobs")
                ->dispatch();

            // Save the batch ID
            $trip->job_batch_id = $batch->id;
            $trip->saveQuietly();

            Log::info("Scheduled " . count($jobs) . " jobs for trip {$trip->id} in new batch {$batch->id}");
        }
    }
}

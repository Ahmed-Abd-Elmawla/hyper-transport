<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueCleanupService
{
    /**
     * Delete pending (unreserved) StartTripJob and EndTripJob rows for a given trip from the database queue.
     * Returns the number of deleted jobs.
     */
    public static function deletePendingTripJobs(int $tripId): int
    {
        try {
            // Get all unreserved jobs that might be trip-related
            $jobs = DB::table('jobs')
                ->select('id', 'payload')
                ->whereNull('reserved_at')
                ->get();

            $jobIdsToDelete = [];

            foreach ($jobs as $job) {
                $payload = $job->payload;

                // Decode the payload to check if it's a trip job
                $decodedPayload = json_decode($payload, true);

                if ($decodedPayload && isset($decodedPayload['displayName'])) {
                    $jobClass = $decodedPayload['displayName'];

                    // Check if it's one of our trip jobs
                    if (in_array($jobClass, ['App\\Jobs\\StartTripJob', 'App\\Jobs\\EndTripJob'])) {
                        // Extract the job data
                        if (isset($decodedPayload['data']['command'])) {
                            $commandData = $decodedPayload['data']['command'];

                            // Try to unserialize the command to get the tripId
                            $unserializedCommand = unserialize($commandData);

                            if ($unserializedCommand && isset($unserializedCommand->tripId) && $unserializedCommand->tripId == $tripId) {
                                $jobIdsToDelete[] = $job->id;
                            }
                        }
                    }
                }
            }

            // Delete the matching jobs
            if (!empty($jobIdsToDelete)) {
                $deletedCount = DB::table('jobs')->whereIn('id', $jobIdsToDelete)->delete();
                Log::info("Deleted {$deletedCount} pending jobs for trip {$tripId}");
                return $deletedCount;
            }

            return 0;
        } catch (\Exception $e) {
            Log::error("Error deleting pending trip jobs for trip {$tripId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Completely delete a batch and all its jobs from the database
     */
    public static function deleteBatchCompletely(?string $batchId): bool
    {
        if (!$batchId) {
            return false;
        }

        try {
            $batchExists = DB::table('job_batches')->where('id', $batchId)->exists();

            if (!$batchExists) {
                return false;
            }

            DB::transaction(function () use ($batchId) {
                // Delete all jobs belonging to this batch
                $deletedJobs = DB::table('jobs')
                    ->where('payload', 'like', '%"batchId":"' . $batchId . '"%')
                    ->delete();

                // Delete the batch record itself
                $deletedBatch = DB::table('job_batches')
                    ->where('id', $batchId)
                    ->delete();

                Log::info("Completely deleted batch {$batchId}: {$deletedJobs} jobs and {$deletedBatch} batch record");
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Error completely deleting batch {$batchId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Alternative method using batch cancellation if batch ID is available
     */
    public static function cancelTripBatch(?string $batchId): bool
    {
        if (!$batchId) {
            return false;
        }

        try {
            $batch = \Illuminate\Support\Facades\Bus::findBatch($batchId);
            if ($batch && !$batch->cancelled()) {
                $batch->cancel();
                Log::info("Cancelled batch {$batchId}");
                return true;
            }
        } catch (\Exception $e) {
            Log::error("Error cancelling batch {$batchId}: " . $e->getMessage());
        }

        return false;
    }
}

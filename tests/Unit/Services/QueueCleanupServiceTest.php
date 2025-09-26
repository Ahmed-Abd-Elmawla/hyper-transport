<?php

use App\Services\QueueCleanupService;
use Illuminate\Support\Facades\DB;

describe('QueueCleanupService', function () {
    beforeEach(function () {
        // Clear any existing jobs
        DB::table('jobs')->truncate();
        DB::table('job_batches')->truncate();
    });

    describe('deletePendingTripJobs', function () {
        it('deletes pending trip jobs from queue', function () {
            // Create a mock job payload for StartTripJob
            $payload = json_encode([
                'uuid' => 'test-uuid',
                'displayName' => 'App\\Jobs\\StartTripJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => null,
                'maxExceptions' => null,
                'failOnTimeout' => false,
                'backoff' => null,
                'timeout' => null,
                'retryUntil' => null,
                'data' => [
                    'commandName' => 'App\\Jobs\\StartTripJob',
                    'command' => serialize(new \App\Jobs\StartTripJob(123, now()))
                ]
            ]);

            // Insert job into queue table
            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => $payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);

            expect(DB::table('jobs')->count())->toBe(1);

            $deletedCount = QueueCleanupService::deletePendingTripJobs(123);

            expect($deletedCount)->toBe(1)
                ->and(DB::table('jobs')->count())->toBe(0);
        });

        it('does not delete jobs for different trip IDs', function () {
            $payload = json_encode([
                'uuid' => 'test-uuid',
                'displayName' => 'App\\Jobs\\StartTripJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => [
                    'commandName' => 'App\\Jobs\\StartTripJob',
                    'command' => serialize(new \App\Jobs\StartTripJob(456, now()))
                ]
            ]);

            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => $payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);

            $deletedCount = QueueCleanupService::deletePendingTripJobs(123);

            expect($deletedCount)->toBe(0)
                ->and(DB::table('jobs')->count())->toBe(1);
        });

        it('does not delete reserved jobs', function () {
            $payload = json_encode([
                'uuid' => 'test-uuid',
                'displayName' => 'App\\Jobs\\StartTripJob',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => [
                    'commandName' => 'App\\Jobs\\StartTripJob',
                    'command' => serialize(new \App\Jobs\StartTripJob(123, now()))
                ]
            ]);

            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => $payload,
                'attempts' => 0,
                'reserved_at' => now()->timestamp, // Job is reserved
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);

            $deletedCount = QueueCleanupService::deletePendingTripJobs(123);

            expect($deletedCount)->toBe(0)
                ->and(DB::table('jobs')->count())->toBe(1);
        });
    });

    describe('deleteBatchCompletely', function () {
        it('deletes batch and associated jobs', function () {
            // Create a batch
            $batchId = 'test-batch-id';
            DB::table('job_batches')->insert([
                'id' => $batchId,
                'name' => 'test-batch',
                'total_jobs' => 2,
                'pending_jobs' => 2,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => '[]',
                'cancelled_at' => null,
                'created_at' => now()->timestamp,
                'finished_at' => null,
            ]);

            // Create jobs in the batch (using the correct payload format)
            for ($i = 0; $i < 2; $i++) {
                DB::table('jobs')->insert([
                    'queue' => 'default',
                    'payload' => json_encode([
                        'batchId' => $batchId,
                        'data' => ['commandName' => 'TestJob']
                    ]),
                    'attempts' => 0,
                    'reserved_at' => null,
                    'available_at' => now()->timestamp,
                    'created_at' => now()->timestamp,
                ]);
            }

            expect(DB::table('job_batches')->count())->toBe(1)
                ->and(DB::table('jobs')->count())->toBe(2);

            $result = QueueCleanupService::deleteBatchCompletely($batchId);

            expect($result)->toBeTrue()
                ->and(DB::table('job_batches')->count())->toBe(0)
                ->and(DB::table('jobs')->count())->toBe(0);
        });

        it('handles non-existent batch gracefully', function () {
            $result = QueueCleanupService::deleteBatchCompletely('non-existent');

            expect($result)->toBeFalse();
        });

        it('handles null batch ID gracefully', function () {
            $result = QueueCleanupService::deleteBatchCompletely(null);

            expect($result)->toBeFalse();
        });
    });
});
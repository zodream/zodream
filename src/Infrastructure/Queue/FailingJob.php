<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

use Exception;
use Zodream\Infrastructure\Queue\Events\JobFailed;
use Zodream\Infrastructure\Queue\Jobs\Job;

class FailingJob {
    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param string $connectionName
     * @param Job $job
     * @param Exception|null $e
     * @return void
     * @throws \Exception
     */
    public static function handle(string $connectionName, Job $job, ?Exception $e = null): void {
        $job->markAsFailed();

        if ($job->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $job->delete();

            $job->failed($e);
        } finally {
            event(new JobFailed(
                $connectionName, $job, $e ?: new \RuntimeException()
            ));
        }
    }
}
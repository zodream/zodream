<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

use Throwable;
use Zodream\Infrastructure\Queue\Jobs\Job;

trait InteractsWithQueue {
    /**
     * The underlying queue job instance.
     *
     * @var Job|null
     */
    protected Job|null $job = null;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void {
        $this->job?->delete();
    }

    /**
     * Fail the job from the queue.
     *
     * @param Throwable|null $exception
     * @return void
     * @throws \Exception
     */
    public function fail(Throwable|null $exception = null): void {
        if ($this->job) {
            FailingJob::handle($this->job->getConnectionName(), $this->job, $exception);
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release(int $delay = 0): void {
        $this->job?->release($delay);
    }

    /**
     * Set the base queue job instance.
     *
     * @param  Job  $job
     * @return $this
     */
    public function setJob(Job $job) {
        $this->job = $job;

        return $this;
    }
}

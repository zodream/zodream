<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Jobs;

use stdClass;
use Zodream\Infrastructure\Queue\DatabaseQueue;

class DatabaseJob extends Job {

    /**
     * Create a new job instance.
     *
     * @param  DatabaseQueue  $database
     * @param  stdClass  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(
        protected DatabaseQueue $database,
        protected stdClass $job,
        protected string $connectionName,
        protected string $queue)
    {
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return mixed
     */
    public function release(int $delay = 0): void {
        parent::release($delay);

        $this->delete();

        $this->database->release($this->queue, $this->job, $delay);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, $this->job->id);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return $this->job->id;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->job->payload;
    }
}
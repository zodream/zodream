<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Jobs;

use Zodream\Infrastructure\Queue\RedisQueue;

class RedisJob extends Job {

    /**
     * The JSON decoded version of "$job".
     *
     * @var array
     */
    protected array $decoded;

    /**
     * Create a new job instance.
     *
     * @param  RedisQueue  $redis
     * @param  string  $job
     * @param  string  $reserved
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(
        protected RedisQueue $redis,
        protected string $job,
        protected string $reserved,
        protected string $connectionName,
        protected string $queue)
    {
        // The $job variable is the original job JSON as it existed in the ready queue while
        // the $reserved variable is the raw JSON in the reserved queue. The exact format
        // of the reserved job is required in order for us to properly delete its data.

        $this->decoded = $this->payload();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string {
        return $this->job;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void {
        parent::delete();

        $this->redis->deleteReserved($this->queue, $this);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release(int $delay = 0): void
    {
        parent::release($delay);

        $this->redis->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return ($this->decoded['attempts'] ?? null) + 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return (string)$this->decoded['id'] ?? '';
    }

    /**
     * Get the underlying Redis factory implementation.
     *
     * @return RedisQueue
     */
    public function getRedisQueue()
    {
        return $this->redis;
    }

    /**
     * Get the underlying reserved Redis job.
     *
     * @return string
     */
    public function getReservedJob(): string
    {
        return $this->reserved;
    }
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;


use Zodream\Infrastructure\Queue\Jobs\Job;

class NullQueue extends Queue {
    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(string|null $queue = null): int {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(mixed $job, mixed $data = '', string|null $queue = null): mixed {
        //
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw(string $payload, string|null $queue = null, array $options = []): mixed {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(int $delay, mixed $job, mixed $data = '', string|null $queue = null): mixed
    {
        //
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return Job|null
     */
    public function pop(string|null $queue = null): Job|null
    {
        //
    }
}
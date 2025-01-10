<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;


use Zodream\Database\Engine\Redis;
use Zodream\Database\RedisManager;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Error\Exception;
use Zodream\Infrastructure\Queue\Jobs\Job;
use Zodream\Infrastructure\Queue\Jobs\RedisJob;

class RedisQueue extends Queue {
    protected array $configs = [
        'connection' => '',
        'default' => 'default',
        'retryAfter' => 60,
        'blockFor' => null,
    ];

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     * @throws \RedisException
     */
    public function size(string|null $queue = null): int
    {
        $queue = $this->getQueue($queue);

        return $this->getConnection()->getDriver()->eval(
            LuaScripts::size(), 3, $queue, $queue.':delayed', $queue.':reserved'
        );
    }

    /**
     * Push a new job onto the queue.
     *
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws Exception
     */
    public function push(mixed $job, mixed $data = '', string|null $queue = null): mixed
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw(string $payload, string|null $queue = null, array $options = []): mixed
    {
        $this->getConnection()->rpush($this->getQueue($queue), $payload);

        return json_decode($payload, true)['id'] ?? null;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws Exception
     */
    public function later(int $delay, mixed $job, mixed $data = '', string|null $queue = null): mixed
    {
        return $this->laterRaw($delay, $this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw job onto the queue after a delay.
     *
     * @param int $delay
     * @param string $payload
     * @param string|null $queue
     * @return mixed
     */
    protected function laterRaw(int $delay, string $payload, string|null $queue = null)
    {
        $this->getConnection()->zadd(
            $this->getQueue($queue).':delayed', time() + $delay, $payload
        );

        return json_decode($payload, true)['id'] ?? null;
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param string $job
     * @param mixed $data
     * @return array
     */
    protected function createPayloadArray(string $job, mixed $data = ''): array
    {
        return array_merge(parent::createPayloadArray($job, $data), [
            'id' => $this->getRandomId(),
            'attempts' => 0,
        ]);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return Job|null
     */
    public function pop(string|null $queue = null): Job|null
    {
        $this->migrate($prefixed = $this->getQueue($queue));

        if (empty($nextJob = $this->retrieveNextJob($prefixed))) {
            return null;
        }

        list($job, $reserved) = $nextJob;

        if ($reserved) {
            return new RedisJob(
                $this, $job,
                $reserved, $this->connectionName, $queue ?: $this->configs['default']
            );
        }
        return null;
    }

    /**
     * Migrate any delayed or expired jobs onto the primary queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function migrate(string $queue): void
    {
        $this->migrateExpiredJobs($queue.':delayed', $queue);

        if (! is_null($this->configs['retryAfter'])) {
            $this->migrateExpiredJobs($queue.':reserved', $queue);
        }
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string  $from
     * @param  string  $to
     * @return array
     */
    public function migrateExpiredJobs(string $from, string $to): array
    {
        return $this->getConnection()->getDriver()->eval(
            LuaScripts::migrateExpiredJobs(), [$from, $to, time()], 2
        );
    }

    /**
     * Retrieve the next job from the queue.
     *
     * @param  string  $queue
     * @return array
     */
    protected function retrieveNextJob(string $queue)
    {
        if (! is_null($this->configs['blockFor'])) {
            return $this->blockingPop($queue);
        }

        return $this->getConnection()->getDriver()->eval(
            LuaScripts::pop(), [$queue, $queue.':reserved',
            time() + $this->configs['retryAfter']], 2
        );
    }

    /**
     * Retrieve the next job by blocking-pop.
     *
     * @param  string  $queue
     * @return array
     */
    protected function blockingPop(string $queue)
    {
        $rawBody = $this->getConnection()->getDriver()->blpop($queue, $this->configs['blockFor']);

        if (! empty($rawBody)) {
            $payload = json_decode($rawBody[1], true);

            $payload['attempts']++;

            $reserved = json_encode($payload);

            $this->getConnection()->zadd($queue.':reserved', [
                $reserved => time() + $this->configs['retryAfter'],
            ]);

            return [$rawBody[1], $reserved];
        }

        return [null, null];
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param RedisJob  $job
     * @return void
     */
    public function deleteReserved(string $queue, RedisJob  $job)
    {
        $this->getConnection()->getDriver()->zrem($this->getQueue($queue).':reserved', $job->getReservedJob());
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  RedisJob  $job
     * @param  int  $delay
     * @return void
     */
    public function deleteAndRelease(string $queue, RedisJob $job, int $delay) {
        $queue = $this->getQueue($queue);

        $this->getConnection()->getDriver()->eval(
            LuaScripts::release(), [$queue.':delayed', $queue.':reserved',
            $job->getReservedJob(), time() + $delay], 2
        );
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId(): string
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue(string|null $queue): string {
        return 'queues:'.($queue ?: $this->configs['default']);
    }

    /**
     * Get the connection for the queue.
     *
     * @return Redis
     */
    protected function getConnection(): Redis {
        return RedisManager::connection($this->configs['connection']);
    }
}
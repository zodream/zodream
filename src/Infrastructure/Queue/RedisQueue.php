<?php
namespace Zodream\Infrastructure\Queue;


use Zodream\Database\Engine\Redis;
use Zodream\Database\RedisManager;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Queue\Jobs\Job;
use Zodream\Infrastructure\Queue\Jobs\RedisJob;

class RedisQueue extends Queue {
    protected $configs = [
        'connection' => '',
        'default' => 'default',
        'retryAfter' => 60,
        'blockFor' => null,
    ];

    public function __construct() {
        $this->loadConfigs();
    }

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);

        return $this->getConnection()->getDriver()->eval(
            LuaScripts::size(), 3, $queue, $queue.':delayed', $queue.':reserved'
        );
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  object|string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->getConnection()->rpush($this->getQueue($queue), $payload);

        return json_decode($payload, true)['id'] ?? null;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  object|string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->laterRaw($delay, $this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $payload
     * @param  string  $queue
     * @return mixed
     */
    protected function laterRaw($delay, $payload, $queue = null)
    {
        $this->getConnection()->zadd(
            $this->getQueue($queue).':delayed', time() + $delay, $payload
        );

        return json_decode($payload, true)['id'] ?? null;
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @return string
     */
    protected function createPayloadArray($job, $data = '')
    {
        return array_merge(parent::createPayloadArray($job, $data), [
            'id' => $this->getRandomId(),
            'attempts' => 0,
        ]);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return Job|null
     */
    public function pop($queue = null)
    {
        $this->migrate($prefixed = $this->getQueue($queue));

        if (empty($nextJob = $this->retrieveNextJob($prefixed))) {
            return;
        }

        list($job, $reserved) = $nextJob;

        if ($reserved) {
            return new RedisJob(
                $this, $job,
                $reserved, $this->connectionName, $queue ?: $this->configs['default']
            );
        }
    }

    /**
     * Migrate any delayed or expired jobs onto the primary queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function migrate($queue)
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
    public function migrateExpiredJobs($from, $to)
    {
        return $this->getConnection()->getDriver()->eval(
            LuaScripts::migrateExpiredJobs(), 2, $from, $to, time()
        );
    }

    /**
     * Retrieve the next job from the queue.
     *
     * @param  string  $queue
     * @return array
     */
    protected function retrieveNextJob($queue)
    {
        if (! is_null($this->configs['blockFor'])) {
            return $this->blockingPop($queue);
        }

        return $this->getConnection()->getDriver()->eval(
            LuaScripts::pop(), 2, $queue, $queue.':reserved',
            time() + $this->configs['retryAfter']
        );
    }

    /**
     * Retrieve the next job by blocking-pop.
     *
     * @param  string  $queue
     * @return array
     */
    protected function blockingPop($queue)
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
    public function deleteReserved($queue, $job)
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
    public function deleteAndRelease($queue, $job, $delay) {
        $queue = $this->getQueue($queue);

        $this->getConnection()->getDriver()->eval(
            LuaScripts::release(), 2, $queue.':delayed', $queue.':reserved',
            $job->getReservedJob(), time() + $delay
        );
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue) {
        return 'queues:'.($queue ?: $this->configs['default']);
    }

    /**
     * Get the connection for the queue.
     *
     * @return Redis
     */
    protected function getConnection() {
        return RedisManager::connection($this->configs['connection']);
    }
}
<?php
namespace Zodream\Infrastructure\Queue;

use Zodream\Database\DB;
use Zodream\Database\Query\Builder;
use Zodream\Infrastructure\Queue\Jobs\DatabaseJob;
use Zodream\Infrastructure\Queue\Jobs\DatabaseJobRecord;

class DatabaseQueue extends Queue {

    protected $configs = [
        'table' => '',
        'default' => 'default',
        'retryAfter' => 60
    ];

    public function __construct() {
        $this->loadConfigs();
    }
    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     * @return int
     */
    public function size($queue = null) {
        return DB::table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->count();
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->availableAt();

        return DB::table($this->table)->insert(array_map(
            function ($job) use ($queue, $data, $availableAt) {
                return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
            }, (array) $jobs));
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null) {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  int $delay
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string  $queue
     * @param  DatabaseJobRecord  $job
     * @param  int  $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase($queue, $job->payload, $delay, $job->attempts);
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param  string|null $queue
     * @param  string $payload
     * @param  int $delay
     * @param  int $attempts
     * @return mixed
     * @throws \Exception
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        return DB::table($this->configs['table'])->insert($this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, time() + $delay, $attempts
        ));
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => time(),
            'payload' => $payload,
        ];
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return null
     * @throws \Exception|\Throwable
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            $this->marshalJob($queue, $job);
        }
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null  $queue
     * @return DatabaseJobRecord|null
     */
    protected function getNextAvailableJob($queue)
    {
        $job = DB::table($this->configs['table'])
            ->where('queue', $this->getQueue($queue))
            ->where(function ($query) {
                $this->isAvailable($query);
                $this->isReservedButExpired($query);
            })
            ->orderBy('id', 'asc')
            ->first();

        return $job ? new DatabaseJobRecord((object) $job) : null;
    }

    /**
     * Modify the query to check for available jobs.
     *
     * @param  Builder  $query
     * @return void
     */
    protected function isAvailable($query)
    {
        $query->where(function (Builder $query) {
            $query->whereNull('reserved_at')
                ->where('available_at', '<=', time());
        });
    }

    /**
     * Modify the query to check for jobs that are reserved but have expired.
     *
     * @param  Builder  $query
     * @return void
     */
    protected function isReservedButExpired($query)
    {
        $expiration = time() + $this->configs['retryAfter'];

        $query->orWhere(function ($query) use ($expiration) {
            $query->where('reserved_at', '<=', $expiration);
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  string  $queue
     * @param  DatabaseJobRecord  $job
     * @return DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
     *
     */
    protected function markJobAsReserved($job)
    {
        DB::table($this->configs['table'])->where('id', $job->id)->update([
            'reserved_at' => $job->touch(),
            'attempts' => $job->increment(),
        ]);
        return $job;
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     * @throws \Exception|\Throwable
     */
    public function deleteReserved($queue, $id)
    {
        DB::table($this->configs['table'])->where('id', $id)->delete();
    }


    public function getQueue($queue) {
        return $queue ?: $this->configs['default'];
    }
}
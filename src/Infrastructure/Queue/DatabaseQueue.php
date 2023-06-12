<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

use stdClass;
use Zodream\Database\Contracts\SqlBuilder;
use Zodream\Database\DB;
use Zodream\Database\Query\Builder;
use Zodream\Database\Schema\Table;
use Zodream\Infrastructure\Error\Exception;
use Zodream\Infrastructure\Queue\Jobs\DatabaseJob;
use Zodream\Infrastructure\Queue\Jobs\DatabaseJobRecord;
use Zodream\Infrastructure\Queue\Jobs\Job;

class DatabaseQueue extends Queue {

    protected array $configs = [
        'table' => 'queue',
        'default' => 'default',
        'retryAfter' => 60
    ];

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int {
        return $this->getConnection()
            ->where('queue', $this->getQueue($queue))
            ->count();
    }

    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): void {
        $queue = $this->getQueue($queue);

        $availableAt = time();

        $this->getConnection()->insert(array_map(
            function ($job) use ($queue, $data, $availableAt) {
                return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
            }, $jobs));
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws Exception
     */
    public function push(mixed $job, mixed $data = '', ?string $queue = null): mixed {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = []): mixed
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws Exception
     */
    public function later(int $delay, mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string $queue
     * @param  stdClass $job
     * @param  int $delay
     * @return mixed
     * @throws \Exception
     */
    public function release(string $queue, stdClass $job, int $delay)
    {
        return $this->pushToDatabase($queue, (string)$job->payload, $delay, intval($job->attempts));
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
    protected function pushToDatabase(?string $queue, string $payload, int $delay = 0, int $attempts = 0) {
        return $this->getConnection()->insert($this->buildDatabaseRecord(
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
    protected function buildDatabaseRecord(?string $queue, string $payload, int $availableAt, int $attempts = 0) {
        return [
            'queue' => $queue,
            'attempts' => $attempts,
            'reserved_at' => 0,
            'available_at' => $availableAt,
            'created_at' => time(),
            'payload' => $payload,
        ];
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return Job|null
     * @throws \Exception
     */
    public function pop(?string $queue = null): ?Job {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            return $this->marshalJob($queue, $job);
        }
        return null;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null  $queue
     * @return DatabaseJobRecord|null
     */
    protected function getNextAvailableJob(?string $queue): ?DatabaseJobRecord {
        $job = $this->getConnection()
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
    protected function isAvailable(SqlBuilder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where('reserved_at', 0)
                ->where('available_at', '<=', time());
        });
    }

    /**
     * Modify the query to check for jobs that are reserved but have expired.
     *
     * @param  Builder  $query
     * @return void
     */
    protected function isReservedButExpired(SqlBuilder $query): void
    {
        $expiration = time() + $this->configs['retryAfter'];

        $query->orWhere(function ($query) use ($expiration) {
            $query->where('reserved_at', '<=', $expiration);
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  string $queue
     * @param  DatabaseJobRecord $job
     * @return DatabaseJob
     * @throws \Exception
     */
    protected function marshalJob(string $queue, DatabaseJobRecord $job): DatabaseJob
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @throws \Exception
     */
    protected function markJobAsReserved($job)
    {
        $this->getConnection()->where('id', $job->id)->update([
            'reserved_at' => $job->touch(),
            'attempts' => $job->increment(),
        ]);
        return $job;
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $queue
     * @param string|int $id
     * @return void
     */
    public function deleteReserved(string $queue, string|int $id)
    {
        $this->getConnection()->where('id', $id)->delete();
    }


    public function getQueue(?string $queue) {
        return $queue ?: $this->configs['default'];
    }

    /**
     * Get the connection for the queue.
     *
     * @return Builder
     */
    protected function getConnection(): SqlBuilder {
        return DB::table($this->configs['table'], $this->configs['connection']);
    }

    public static function createMigration(Table $table): void {
        $table->id();
        $table->string('queue', 200)->index();
        $table->column('payload')->longtext();
        $table->uint('attempts', 2)->default(0);
        $table->timestamp('reserved_at');
        $table->timestamp('available_at');
        $table->timestamp('created_at');
    }
}
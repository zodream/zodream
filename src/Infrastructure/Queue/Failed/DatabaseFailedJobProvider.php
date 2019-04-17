<?php

namespace Zodream\Infrastructure\Queue\Failed;


use Zodream\Database\DB;
use Zodream\Database\Query\Builder;

class DatabaseFailedJobProvider implements FailedJobProviderInterface {

    /**
     * The database connection name.
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table;

    public function __construct($table) {
        $this->table = $table;
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = time();

        $exception = (string) $exception;

        return $this->getTable()->insert(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at'
        ));
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all() {
        return $this->getTable()->orderBy('id', 'desc')->get();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        return $this->getTable()->where('id', $id)->first();
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
     *
     * @return Builder
     */
    protected function getTable()
    {
        return DB::table($this->table);
    }
}

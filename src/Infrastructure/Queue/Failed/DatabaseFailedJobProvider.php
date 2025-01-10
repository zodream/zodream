<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Failed;


use Exception;
use Zodream\Database\Contracts\SqlBuilder;
use Zodream\Database\DB;
use Zodream\Database\Query\Builder;
use Zodream\Database\Schema\Table;

class DatabaseFailedJobProvider implements FailedJobProviderInterface {

    /**
     * The database connection name.
     *
     * @var string
     */
    protected string $database = '';


    public function __construct(
        protected string $table) {
    }

    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param Exception $exception
     * @return int|string|null
     * @throws Exception
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception): null|int|string
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
    public function all(): array {
        return $this->getTable()->orderBy('id', 'desc')->get();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find(string|int $id): array|null
    {
        return $this->getTable()->where('id', $id)->first();
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget(string|int $id): bool {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
     *
     * @return Builder
     */
    protected function getTable(): SqlBuilder {
        return DB::table($this->table);
    }

    public static function createMigration(Table $table) {
        $table->id();
        $table->column('connection')->text();
        $table->column('queue')->text();
        $table->column('payload')->longtext();
        $table->column('exception')->longtext();
        $table->timestamp('failed_at');
    }
}

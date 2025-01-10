<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Failed;

use Exception;

interface FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param Exception $exception
     * @return int|string|null
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception): null|int|string;

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find(string|int $id): array|null;

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget(string|int $id): bool;

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush(): void;
}

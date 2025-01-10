<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Jobs;


use Exception;
use Zodream\Helpers\Json;

abstract class Job {
    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected mixed $instance = null;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected bool $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected bool $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected bool $failed = false;

    /**
     * The name of the connection the job belongs to.
     */
    protected string $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected string $queue;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    abstract public function getJobId(): string;

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    abstract public function getRawBody(): string;

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void {
        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        ($this->instance = $this->resolve($class))->{$method}($this, $payload['data']);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void {
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release(int $delay = 0): void {
        $this->released = true;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased(): bool {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased(): bool {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed(): bool {
        return $this->failed;
    }

    abstract public function attempts(): int;

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed(): void {
        $this->failed = true;
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  Exception  $e
     * @return void
     */
    public function failed(Exception $e): void
    {
        $this->markAsFailed();

        $payload = $this->payload();

        list($class, $method) = JobName::parse($payload['job']);

        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e);
        }
    }

    /**
     * Resolve the given class.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve(string $class): mixed
    {
        return app($class);
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        return Json::decode($this->getRawBody());
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries(): int|null {
        return $this->payload()['maxTries'] ?? null;
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout(): int|null
    {
        return $this->payload()['timeout'] ?? null;
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt(): int|null
    {
        return $this->payload()['timeoutAt'] ?? null;
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->payload()['job'];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName(): string
    {
        return JobName::resolve($this->getName(), $this->payload());
    }

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }
}
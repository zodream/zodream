<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Infrastructure\Error\Exception;
use Zodream\Infrastructure\Queue\Jobs\Job;

abstract class Queue extends ConfigObject {
    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected string $connectionName;

    public function __construct(array $configs) {
        $this->configs = array_merge($this->configs, $configs);
    }


    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    abstract public function size(string|null $queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    abstract public function push(mixed $job, mixed $data = '', string|null $queue = null): mixed;


    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    abstract public function pushRaw(string $payload, string|null $queue = null, array $options = []): mixed;

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    abstract public function later(int $delay, mixed $job, mixed $data = '', string|null $queue = null): mixed;

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return Job|null
     */
    abstract public function pop(string|null $queue = null): Job|null;

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function pushOn(string $queue, string $job, mixed $data = ''): mixed {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string  $queue
     * @param  int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function laterOn(string $queue, int $delay, string $job, mixed $data = ''): mixed {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string|null $queue
     * @return void
     */
    public function bulk(array $jobs, mixed $data = '', string|null $queue = null): void {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string $job
     * @param  mixed $data
     * @return string
     *
     * @throws Exception
     */
    protected function createPayload(string $job, mixed $data = '') : string {
        $payload = json_encode($this->createPayloadArray($job, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(
                'Unable to JSON encode payload. Error code: '.json_last_error()
            );
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @return array
     */
    protected function createPayloadArray(string $job, mixed $data = ''): array
    {
        return is_object($job)
            ? $this->createObjectPayload($job)
            : $this->createStringPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload(mixed $job): array
    {
        return [
            'displayName' => $this->getDisplayName($job),
            'job' => 'Zodream\Infrastructure\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => $this->getJobExpiration($job),
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
        ];
    }

    /**
     * Get the display name for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getDisplayName(mixed $job): string
    {
        return method_exists($job, 'displayName')
            ? $job->displayName() : get_class($job);
    }

    /**
     * Get the expiration timestamp for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function getJobExpiration(mixed $job): mixed {
        if (! method_exists($job, 'retryUntil') && ! isset($job->timeoutAt)) {
            return 0;
        }
        return $job->timeoutAt ?? $job->retryUntil();
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param string|null $job
     * @param mixed $data
     * @return array
     */
    protected function createStringPayload(string|null $job, mixed $data): array {
        return [
            'displayName' => is_string($job) ? explode('@', $job)[0] : null,
            'job' => $job,
            'maxTries' => null,
            'timeout' => null,
            'data' => $data,
        ];
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName(string $name) {
        $this->connectionName = $name;

        return $this;
    }
}
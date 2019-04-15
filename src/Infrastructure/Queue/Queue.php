<?php
namespace Zodream\Infrastructure\Queue;

use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Infrastructure\Error\Exception;

abstract class Queue extends ConfigObject {
    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    protected $configKey = 'queue';

    /**
     * Get the size of the queue.
     *
     * @param  string  $queue
     * @return int
     */
    abstract public function size($queue = null);

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    abstract public function push($job, $data = '', $queue = null);


    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    abstract public function pushRaw($payload, $queue = null, array $options = []);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  int  $delay
     * @param  string|object  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    abstract public function later($delay, $job, $data = '', $queue = null);

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return Job|null
     */
    abstract public function pop($queue = null);

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string $job
     * @param  mixed $data
     * @return string
     *
     * @throws Exception
     */
    protected function createPayload($job, $data = '') {
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
    protected function createPayloadArray($job, $data = '')
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
    protected function createObjectPayload($job)
    {
        return [
            'displayName' => $this->getDisplayName($job),
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
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
    protected function getDisplayName($job)
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
    public function getJobExpiration($job) {
        if (! method_exists($job, 'retryUntil') && ! isset($job->timeoutAt)) {
            return 0;
        }
        return $job->timeoutAt ?? $job->retryUntil();
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @return array
     */
    protected function createStringPayload($job, $data) {
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
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

use Zodream\Infrastructure\Queue\InteractsWithQueue;
use Zodream\Infrastructure\Queue\Jobs\Job;
use Zodream\Infrastructure\Queue\ShouldQueue;

class CallQueuedListener implements ShouldQueue {

    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 0;

    /**
     * The timestamp indicating when the job should timeout.
     *
     * @var int
     */
    public int $timeoutAt = 0;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 0;

    /**
     * Create a new job instance.
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $data
     * @return void
     */
    public function __construct(
        public string $class,
        public string $method,
        public array $data) {
    }

    /**
     * Handle the queued job.
     *
     * @return void
     */
    public function handle(): void {

        $this->prepareData();

        $handler = $this->setJobInstanceIfNecessary(
            $this->job, app($this->class)
        );

        call_user_func_array(
            [$handler, $this->method], $this->data
        );
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance) {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * The event instance and the exception will be passed.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e) {
        $this->prepareData();

        $handler = app($this->class);

        $parameters = array_merge($this->data, [$e]);

        if (method_exists($handler, 'failed')) {
            call_user_func_array([$handler, 'failed'], $parameters);
        }
    }

    /**
     * Unserialize the data if needed.
     *
     * @return void
     */
    protected function prepareData() {
        if (is_string($this->data)) {
            $this->data = unserialize($this->data);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName(): string {
        return $this->class;
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone() {
        $this->data = array_map(function ($data) {
            return is_object($data) ? clone $data : $data;
        }, $this->data);
    }
}
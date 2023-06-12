<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

use Exception;
use Zodream\Database\Model\ModelNotFoundException;
use Zodream\Infrastructure\Queue\Jobs\Job;

/**
 * 相关代码来源于 laravel 并进行相应的改变
 * @see https://github.com/laravel/framework
 */
class CallQueuedHandler {

    /**
     * Handle the queued job.
     *
     * @param  Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data): void {
        try {
            $command = $this->setJobInstanceIfNecessary(
                $job, unserialize($data['command'])
            );
        } catch (ModelNotFoundException $e) {
            $this->handleModelNotFound($job, $e);
            return;
        }

        event()->dispatchNow(
            $command, $this->resolveHandler($job, $command)
        );

        if (! $job->hasFailed() && ! $job->isReleased()) {
            $this->ensureNextJobInChainIsDispatched($command);
        }

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Resolve the handler for the given command.
     *
     * @param  Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function resolveHandler(Job $job, mixed $command): mixed {
        $handler = event()->getCommandHandler($command) ?: null;

        if ($handler) {
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, mixed $instance): mixed
    {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Ensure the next job in the chain is dispatched if applicable.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureNextJobInChainIsDispatched(mixed $command): void
    {
        if (method_exists($command, 'dispatchNextJobInChain')) {
            $command->dispatchNextJobInChain();
        }
    }

    /**
     * Handle a model not found exception.
     *
     * @param  Job  $job
     * @param  Exception  $e
     * @return void
     */
    protected function handleModelNotFound(Job $job, Exception $e): void
    {
        $class = $job->resolveName();

        try {
            $shouldDelete = (new \ReflectionClass($class))
                    ->getDefaultProperties()['deleteWhenMissingModels'] ?? false;
        } catch (Exception $e) {
            $shouldDelete = false;
        }

        if ($shouldDelete) {
            $job->delete();
            return;
        }

        FailingJob::handle(
            $job->getConnectionName(), $job, $e
        );
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array  $data
     * @param  Exception  $e
     * @return void
     */
    public function failed(array $data, Exception $e): void {
        $command = unserialize($data['command']);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }
}
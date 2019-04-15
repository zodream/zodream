<?php
namespace Zodream\Infrastructure\Queue;

use Zodream\Database\Model\ModelNotFoundException;
use Zodream\Infrastructure\Queue\Jobs\Job;

class CallQueuedHandler {
    /**
     * Handle the queued job.
     *
     * @param  Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        try {
            $command = $this->setJobInstanceIfNecessary(
                $job, unserialize($data['command'])
            );
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFound($job, $e);
        }

        $this->dispatcher->dispatchNow(
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
    protected function resolveHandler($job, $command)
    {
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

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
    protected function setJobInstanceIfNecessary(Job $job, $instance)
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
    protected function ensureNextJobInChainIsDispatched($command)
    {
        if (method_exists($command, 'dispatchNextJobInChain')) {
            $command->dispatchNextJobInChain();
        }
    }

    /**
     * Handle a model not found exception.
     *
     * @param  Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function handleModelNotFound(Job $job, $e)
    {
        $class = $job->resolveName();

        try {
            $shouldDelete = (new \ReflectionClass($class))
                    ->getDefaultProperties()['deleteWhenMissingModels'] ?? false;
        } catch (\Exception $e) {
            $shouldDelete = false;
        }

        if ($shouldDelete) {
            return $job->delete();
        }

        return FailingJob::handle(
            $job->getConnectionName(), $job, $e
        );
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array  $data
     * @param  \Exception  $e
     * @return void
     */
    public function failed(array $data, $e)
    {
        $command = unserialize($data['command']);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }
}
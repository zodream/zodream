<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;


use Zodream\Helpers\Str;
use Zodream\Infrastructure\Contracts\ExceptionHandler;
use Zodream\Infrastructure\Error\FatalThrowableError;
use Zodream\Infrastructure\Queue\Jobs\Job;
use Exception;
use Throwable;
/**
 * 相关代码来源于 laravel 并进行相应的改变
 * @see https://github.com/laravel/framework
 */
class Worker {


    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public bool $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
     *
     * @var bool
     */
    public bool $paused = false;


    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  WorkerOptions  $options
     * @return void
     */
    public function daemon(string $connectionName, string $queue, WorkerOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (! $this->daemonShouldRun($options, $connectionName, $queue)) {
                $this->pauseWorker($options, $lastRestart);

                continue;
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
            $job = $this->getNextJob(
                QueueManager::connection($connectionName), $queue
            );

            if ($this->supportsAsyncSignals()) {
                $this->registerTimeoutHandler($job, $options);
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            if ($job) {
                $this->runJob($job, $connectionName, $options);
            } else {
                $this->sleep($options->sleep);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $lastRestart);
        }
    }

    /**
     * Register the worker timeout handler.
     *
     * @param  Job|null  $job
     * @param  WorkerOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler(Job|null $job, WorkerOptions $options)
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
        pcntl_signal(SIGALRM, function () {
            $this->kill(1);
        });

        pcntl_alarm(
            max($this->timeoutForJob($job, $options), 0)
        );
    }

    /**
     * Get the appropriate timeout for the given job.
     *
     * @param  Job|null  $job
     * @param  WorkerOptions  $options
     * @return int
     */
    protected function timeoutForJob(Job|null $job, WorkerOptions $options)
    {
        return $job && ! is_null($job->timeout()) ? $job->timeout() : $options->timeout;
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param  WorkerOptions  $options
     * @param  string  $connectionName
     * @param  string  $queue
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options, string $connectionName, string $queue)
    {
        return ! ((true && ! $options->force) ||
            $this->paused ||
            event(new Events\Looping($connectionName, $queue)) === false);
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param  WorkerOptions  $options
     * @param  int  $lastRestart
     * @return void
     */
    protected function pauseWorker(WorkerOptions $options, int $lastRestart)
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);

        $this->stopIfNecessary($options, $lastRestart);
    }

    /**
     * Stop the process if necessary.
     *
     * @param  WorkerOptions  $options
     * @param  int  $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, int $lastRestart)
    {
        if ($this->shouldQuit) {
            $this->kill();
        }

        if ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        } elseif ($this->queueShouldRestart($lastRestart)) {
            $this->stop();
        }
    }

    /**
     * Process the next job on the queue.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  WorkerOptions  $options
     * @return void
     */
    public function runNextJob(string $connectionName, string $queue, WorkerOptions $options)
    {
        $job = $this->getNextJob(
            QueueManager::connection($connectionName), $queue
        );

        // If we're able to pull a job off of the stack, we will process it and then return
        // from this method. If there is no job on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
        if ($job) {
            return $this->runJob($job, $connectionName, $options);
        }

        $this->sleep($options->sleep);
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param  Queue  $connection
     * @param  string  $queue
     * @return Job|null
     */
    protected function getNextJob(Queue $connection, string $queue)
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                if (! is_null($job = $connection->pop($queue))) {
                    return $job;
                }
            }
        } catch (Exception $e) {
            app(ExceptionHandler::class)->report($e);

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        } catch (Throwable $e) {
            app(ExceptionHandler::class)->report($e = new FatalThrowableError($e));

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        }
    }

    /**
     * Process the given job.
     *
     * @param  Job  $job
     * @param  string  $connectionName
     * @param  WorkerOptions  $options
     * @return void
     */
    protected function runJob(Job $job, string $connectionName, WorkerOptions $options)
    {
        try {
            return $this->process($connectionName, $job, $options);
        } catch (Exception $e) {
            app(ExceptionHandler::class)->report($e);

            $this->stopWorkerIfLostConnection($e);
        } catch (Throwable $e) {
            app(ExceptionHandler::class)->report($e = new FatalThrowableError($e));

            $this->stopWorkerIfLostConnection($e);
        }
    }

    /**
     * Stop the worker if we have lost connection to a database.
     *
     * @param Throwable $e
     * @return void
     */
    protected function stopWorkerIfLostConnection(Throwable $e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->shouldQuit = true;
        }
    }

    /**
     * Process the given job from the queue.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param  WorkerOptions  $options
     * @return void
     *
     * @throws Throwable
     */
    public function process(string $connectionName, Job $job, WorkerOptions $options)
    {
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over its maximum attempt limits, which could primarily happen when this job is
            // continually timing out and not actually throwing any exceptions from itself.
            $this->raiseBeforeJobEvent($connectionName, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int) $options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $job->fire();

            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableError($e)
            );
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param  WorkerOptions  $options
     * @param Exception $e
     * @return void
     *
     * @throws Exception
     */
    protected function handleJobException(string $connectionName, Job $job, WorkerOptions $options, Exception $e)
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            if (! $job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $connectionName, $job, (int) $options->maxTries, $e
                );
            }

            $this->raiseExceptionOccurredJobEvent(
                $connectionName, $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            if (! $job->isDeleted() && ! $job->isReleased() && ! $job->hasFailed()) {
                $job->release($options->delay);
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param  int  $maxTries
     * @return void
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts(string $connectionName, Job $job, int $maxTries)
    {
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        $timeoutAt = $job->timeoutAt();

        if ($timeoutAt && time() <= $timeoutAt) {
            return;
        }

        if (! $timeoutAt && ($maxTries === 0 || $job->attempts() <= $maxTries)) {
            return;
        }

        $this->failJob($connectionName, $job, $e = new Exception(
            $job->resolveName().' has been attempted too many times or run too long. The job may have previously timed out.'
        ));

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param  int  $maxTries
     * @param Exception $e
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts(string $connectionName, Job $job,
                                                              int $maxTries, Exception $e)
    {
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        if ($job->timeoutAt() && $job->timeoutAt() <= time()) {
            $this->failJob($connectionName, $job, $e);
        }

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($connectionName, $job, $e);
        }
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param Exception $e
     * @return void
     */
    protected function failJob(string $connectionName, Job $job, Exception $e): void
    {
        FailingJob::handle($connectionName, $job, $e);
    }

    /**
     * Raise the before queue job event.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent(string $connectionName, Job $job)
    {
        event(new Events\JobProcessing(
            $connectionName, $job
        ));
    }

    /**
     * Raise the after queue job event.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent(string $connectionName, Job $job)
    {
        event(new Events\JobProcessed(
            $connectionName, $job
        ));
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param Exception $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(string $connectionName, Job $job, Exception $e)
    {
        event(new Events\JobExceptionOccurred(
            $connectionName, $job, $e
        ));
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart(int|null $lastRestart): bool
    {
        return $this->getTimestampOfLastQueueRestart() !== $lastRestart;
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart(): int|null {
        return cache('zodream:queue:restart');
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
        });
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded(int $memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    public function stop(int $status = 0): void
    {
        event(new Events\WorkerStopping);

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill(int $status = 0): void
    {
        event(new Events\WorkerStopping);

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int|float   $seconds
     * @return void
     */
    public function sleep(int|float $seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param Throwable $e
     * @return bool
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'Name or service not known',
        ]);
    }
}
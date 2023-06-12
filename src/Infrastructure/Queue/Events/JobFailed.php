<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Events;

use Zodream\Infrastructure\Queue\Jobs\Job;

class JobFailed {

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct(
        public string $connectionName,
        public Job $job,
        public \Exception $exception
    )
    {
    }
}

<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Events;

use Zodream\Infrastructure\Queue\Jobs\Job;

class JobProcessed {

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  Job  $job
     * @return void
     */
    public function __construct(
        public string $connectionName,
        public Job $job
    )
    {
    }
}

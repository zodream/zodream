<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Events;

class Looping {

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(
        public string $connectionName,
        public string $queue)
    {
    }
}

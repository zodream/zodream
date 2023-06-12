<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;

class WorkerOptions {
    /**
     * Create a new worker options instance.
     *
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     * @return void
     */
    public function __construct(
        public int $delay = 0,
        public int $memory = 128,
        public int $timeout = 60,
        public int $sleep = 3,
        public int $maxTries = 0,
        public bool $force = false)
    {
    }
}
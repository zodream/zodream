<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Jobs;

use stdClass;

class DatabaseJobRecord {

    /**
     * Create a new job record instance.
     *
     * @param  stdClass  $record
     * @return void
     */
    public function __construct(
        protected stdClass $record)
    {
    }

    /**
     * Increment the number of times the job has been attempted.
     *
     * @return int
     */
    public function increment(): int {
        $this->record->attempts++;

        return $this->record->attempts;
    }

    /**
     * Update the "reserved at" timestamp of the job.
     *
     * @return int
     */
    public function touch(): int {
        $this->record->reserved_at = time();

        return $this->record->reserved_at;
    }

    /**
     * Dynamically access the underlying job information.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get(string $key): mixed {
        return $this->record->{$key};
    }
}
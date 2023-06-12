<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue\Jobs;

use Zodream\Helpers\Str;

class JobName {
    /**
     * Parse the given job name into a class / method array.
     *
     * @param  string  $job
     * @return array
     */
    public static function parse(string $job): array {
        return Str::parseCallback($job, 'fire');
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * @param  string  $name
     * @param  array  $payload
     * @return string
     */
    public static function resolve(string $name, array $payload): string {
        if (! empty($payload['displayName'])) {
            return $payload['displayName'];
        }

        return $name;
    }
}
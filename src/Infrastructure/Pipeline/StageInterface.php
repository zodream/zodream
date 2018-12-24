<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
interface StageInterface {
    /**
     * Process the payload
     * @param mixed $payload
     * @return mixed
     */
    public function __invoke($payload);
}
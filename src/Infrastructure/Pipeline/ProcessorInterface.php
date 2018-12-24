<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
interface ProcessorInterface {

    /**
     * Let payload processed the stages.
     * @param $payload
     * @param callable[] ...$stages
     * @return mixed
     */
    public function process($payload, callable ...$stages);
}
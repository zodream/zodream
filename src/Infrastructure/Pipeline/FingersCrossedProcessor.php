<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
class FingersCrossedProcessor implements ProcessorInterface {
    /**
     * @param $payload
     * @param callable[] ...$stages
     * @return mixed
     */
    public function process($payload, callable ...$stages) {
        foreach ($stages as $stage) {
            $payload = $stage($payload);
        }
        return $payload;
    }
}
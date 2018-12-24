<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
class InterruptibleProcessor implements ProcessorInterface {
    /**
     * @var callable
     */
    private $check;

    public function __construct(callable $check) {
        $this->check = $check;
    }

    public function process($payload, callable ...$stages) {
        $check = $this->check;
        foreach ($stages as $stage) {
            $payload = $stage($payload);
            if (true !== $check($payload)) {
                return $payload;
            }
        }
        return $payload;
    }
}
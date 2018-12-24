<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
use InvalidArgumentException;

class Pipeline implements PipelineInterface {

    /**
     * @var callable[]
     */
    private $stages = [];

    /**
     * @var ProcessorInterface
     */
    private $processor;

    /**
     * Constructor.
     *
     * @param callable[]         $stages
     * @param ProcessorInterface $processor
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ProcessorInterface $processor = null, callable ...$stages) {
        $this->processor = $processor ?? new FingersCrossedProcessor;
        $this->stages = $stages;
    }

    /**
     * Create a new Pipeline with append stage.
     * @param callable[] $stages
     * @return mixed
     */
    public function pipe(callable ...$stages) {
        $pipeline = clone $this;
        foreach ($stages as $stage) {
            $pipeline->stages[] = $stage;
        }
        return $pipeline;
    }

    /**
     * Process the payload.
     *
     * @param $payload
     * @return mixed
     */
    public function process($payload) {
        return $this->processor->process($payload, ...$this->stages);
    }

    /**
     * Process the payload
     * @param $payload
     * @return mixed
     */
    public function __invoke($payload) {
        return $this->process($payload);
    }
}
<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
class PipelineBuilder {
    /**
     * @var callable[]
     */
    private $stages = [];

    /**
     * @param callable[] $stages
     * @return self
     */
    public function add(callable ...$stages) {
        foreach ($stages as $stage) {
            $this->stages[] = $stage;
        }
        return $this;
    }

    public function build(ProcessorInterface|null $processor = null): PipelineInterface {
        return new Pipeline($processor, ...$this->stages);
    }
}
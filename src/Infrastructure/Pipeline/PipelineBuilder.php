<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 17:28
 */
class PipelineBuilder {
    /**
     * @var callable[]
     */
    private $stages = [];

    /**
     * Add an stage.
     *
     * @param callable $stage
     *
     * @return $this
     */
    public function add(callable $stage) {
        $this->stages[] = $stage;
        return $this;
    }

    /**
     * Build a new Pipeline object
     *
     * @param  ProcessorInterface|null $processor
     *
     * @return Pipeline
     */
    public function build(ProcessorInterface $processor = null) {
        return new Pipeline($this->stages, $processor);
    }
}
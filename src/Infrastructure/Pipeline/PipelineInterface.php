<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;
/**
 * @see https://github.com/thephpleague/pipeline
 */
interface PipelineInterface extends StageInterface {

    /**
     * Create a new Pipeline with append stage.
     * @param callable[] $stages
     * @return mixed
     */
    public function pipe(callable ...$stages);
}
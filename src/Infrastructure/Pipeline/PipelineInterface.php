<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 16:52
 */
interface PipelineInterface extends StageInterface {

    /**
     * Create a new Pipeline with append stage.
     * @param callable $stage
     * @return mixed
     */
    public function pipe(callable $stage);
}
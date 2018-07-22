<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 16:54
 */
interface ProcessorInterface {

    /**
     * Let payload processed the stages.
     * @param array $stages
     * @param mixed $payload
     * @return mixed
     */
    public function process(array $stages, $payload);
}
<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 16:53
 */
interface StageInterface {
    /**
     * Process the payload
     * @param mixed $payload
     * @return mixed
     */
    public function __invoke($payload);
}
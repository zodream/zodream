<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 17:33
 */
class FingersCrossedProcessor implements ProcessorInterface {
    /**
     * @param array $stages
     * @param mixed $payload
     *
     * @return mixed
     */
    public function process(array $stages, $payload) {
        foreach ($stages as $stage) {
            $payload = call_user_func($stage, $payload);
        }

        return $payload;
    }
}
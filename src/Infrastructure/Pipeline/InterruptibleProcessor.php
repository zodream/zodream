<?php
namespace Zodream\Infrastructure\Pipeline;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/18
 * Time: 17:33
 */
class InterruptibleProcessor implements ProcessorInterface {
    /**
     * @var callable
     */
    private $check;

    /**
     * InterruptibleProcessor constructor.
     *
     * @param callable $check
     */
    public function __construct(callable $check) {
        $this->check = $check;
    }

    /**
     * @param array $stages
     * @param mixed $payload
     *
     * @return mixed
     */
    public function process(array $stages, $payload) {
        foreach ($stages as $stage) {
            $payload = call_user_func($stage, $payload);
            if (true !== call_user_func($this->check, $payload)) {
                return $payload;
            }
        }

        return $payload;
    }
}
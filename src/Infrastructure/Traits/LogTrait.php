<?php
namespace Zodream\Infrastructure\Traits;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/10
 * Time: 15:08
 */
use Monolog\Logger;
use Zodream\Service\Factory;

trait LogTrait {
    /**
     * 记录日志
     * @param $message
     * @param int $levels
     */
    public function addLog($message, $levels = Logger::INFO) {
        Factory::log()->addRecord($levels, $message);
    }
}
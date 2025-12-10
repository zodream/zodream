<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Concerns;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/10
 * Time: 15:08
 */

use Monolog\Level;

trait LogTrait {
    /**
     * 记录日志
     * @param $message
     * @param int|Level $levels
     */
    public function addLog($message, int|Level $levels = Level::Info) {
        logger()->addRecord($levels, $message);
    }
}
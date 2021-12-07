<?php
namespace Zodream\Infrastructure\Caching;

use Zodream\Database\RedisManager;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/17
 * Time: 22:43
 */
class Redis extends Cache {

    protected array $configs = [
        'gc' => 10,
        'connection' => null,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    public function __construct() {
        $this->loadConfigs();
    }


    protected function getValue($key) {
        return $this->getConnection()->get($key);
    }

    protected function setValue($key, $value, $duration) {
        if ($duration < 0) {
            $duration = 0;
        }
        return $this->getConnection()->setex($key, $duration, $value);
    }

    protected function addValue($key, $value, $duration) {
        return $this->setValue($key, $value, $duration);
    }


    protected function deleteValue($key) {
        return $this->getConnection()->del($key);
    }

    protected function clearValue() {
        return $this->getConnection()->flushDB();
    }

    protected function getConnection() {
        return RedisManager::connection($this->configs['connection']);
    }
}
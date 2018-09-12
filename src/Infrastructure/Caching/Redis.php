<?php
namespace Zodream\Infrastructure\Caching;

use Redis as RedisClient;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/17
 * Time: 22:43
 */
class Redis extends Cache {

    protected $configs = [
        'gc' => 10,
        'host' => '127.0.0.1',
        'port' => 6379,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    /**
     * @var RedisClient
     */
    protected $client;

    public function __construct() {
        $this->loadConfigs();
        $this->client = new RedisClient();
        $this->client->connect($this->configs['host'], $this->configs['port']);
    }


    protected function getValue($key) {
        return $this->client->get($key);
    }

    protected function setValue($key, $value, $duration) {
        if ($duration < 0) {
            $duration = 0;
        }
        return $this->client->setex($key, $duration, $value);
    }

    protected function addValue($key, $value, $duration) {
        return $this->setValue($key, $value, $duration);
    }


    protected function deleteValue($key) {
        return $this->client->delete($key);
    }

    protected function clearValue() {
        return $this->client->flushDB();
    }

    public function close() {
        $this->client->close();
    }
}
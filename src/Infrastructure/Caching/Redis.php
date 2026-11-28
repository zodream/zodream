<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;

use Predis\ClientInterface;
use Zodream\Infrastructure\Pool\RedisPool;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/17
 * Time: 22:43
 */
class Redis extends Cache {

    protected array $configs = [
        'connection' => '',
        'gc' => 10,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    private readonly ClientInterface $clent;

    public function __construct(
        RedisPool $pool
    ) {
        $this->loadConfigs();
        $this->client = $pool->get($this->configs['connection']);
    }


    protected function getValue(string $key) {
        return $this->client->get($key);
    }

    protected function setValue(string $key, mixed $value, int $duration) {
        if ($duration < 0) {
            $duration = 0;
        }
        return $this->client->setex($key, $duration, $value);
    }

    protected function addValue(string $key, mixed $value, int $duration) {
        return $this->setValue($key, $value, $duration);
    }


    protected function deleteValue(string $key) {
        return $this->client->del($key);
    }

    protected function clearValue() {
        return $this->client->flushDB();
    }
}
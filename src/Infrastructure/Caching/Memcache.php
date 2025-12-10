<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;

use \Memcache as Mem;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/17
 * Time: 22:43
 */
class Memcache extends Cache {

    protected array $configs = [
        'gc' => 10,
        'host' => '127.0.0.1',
        'port' => 11211,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    /**
     * @var Mem
     */
    protected $client;

    public function __construct() {
        $this->loadConfigs();
        $this->client = new Mem();
        $this->client->addServer($this->configs['host'], $this->configs['port']);
        $this->client->setCompressThreshold(20000, .2);
    }


    protected function getValue($key) {
        return $this->client->get($key);
    }

    protected function setValue($key, $value, $duration) {
        if ($duration < 0) {
            $duration = 0;
        }
        return $this->client->set($key, $value, MEMCACHE_COMPRESSED, $duration);
    }

    protected function addValue($key, $value, $duration) {
        return $this->client->add($key, $value, MEMCACHE_COMPRESSED, $duration);
    }

    protected function replaceValue($key, $value, $duration) {
        return $this->client->replace($key, $value, MEMCACHE_COMPRESSED, $duration);
    }

    protected function deleteValue($key) {
        return $this->client->delete($key);
    }

    protected function clearValue() {
        return $this->client->flush();
    }

    public function close() {
        return $this->client->close();
    }
}
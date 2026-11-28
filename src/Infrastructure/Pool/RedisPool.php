<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Pool;

use Predis\ClientInterface;
use Predis\Client;

final class RedisPool extends EnginePool {
    /**
     * @var ClientInterface[]
     */
    protected array $engines = [];

    protected string $defaultDriver = Client::class;

    protected string $configKey = 'redis';

    /**
     * @param string $name
     * @return Client
     */
    public function get(string $name = ''): ClientInterface {
        return $this->getEngine($name);
    }
}
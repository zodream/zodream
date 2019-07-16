<?php
namespace Zodream\Infrastructure\Queue;


use Zodream\Database\Manager;
use Zodream\Infrastructure\Traits\SingletonPattern;

class QueueManager extends Manager {
    use SingletonPattern;

    protected $configKey = 'queue';

    /**
     * @var Queue[]
     */
    protected $engines = [];

    protected $defaultDriver = NullQueue::class;

    /**
     * @param string $name
     * @return Queue
     * @throws \Exception
     */
    public static function connection($name = null) {
        return static::getInstance()->getEngine($name);
    }
}
<?php
namespace Zodream\Infrastructure\Queue;


use Zodream\Database\Manager;
use Zodream\Infrastructure\Traits\SingletonPattern;

class QueueManager extends Manager {
    use SingletonPattern;

    /**
     * @var Redis[]
     */
    protected $engines = [];

    protected $defaultDriver = NullQueue::class;

    /**
     * @param string $name
     * @return Queue
     */
    public static function connection($name = null) {
        return static::getInstance()->getEngine($name);
    }
}
<?php
namespace Zodream\Infrastructure\Event;

use Zodream\Infrastructure\Queue\QueueManager;
use Zodream\Infrastructure\Queue\ShouldQueue;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:49
 */
class Action {
    protected $class;
    protected $function;
    protected $file;

    public function __construct($class, $function = null, $file = null) {
        if (is_null($function) && is_string($class) && strpos($class, '@')) {
            list($class, $function) = explode('@', $class, 2);
        }
        $this->class = $class;
        $this->function = $function;
        if (!empty($file) && !is_file($file)) {
            $file = APP_DIR.'/'.ltrim($file, '/');
        }
        $this->file = $file;
    }

    public function run($args = array()) {
        if (is_callable($this->class)) {
            return self::callFunc($this->class, $args);
        }
        if (strpos($this->class, '::') === false &&
            !class_exists($this->class) && !function_exists($this->function)) {
            return require($this->file);
        }
        if (empty($this->class)) {
            return $this->_runWithFunction($args);
        }
        if (!class_exists($this->class)) {
            return false;
        }
        if ($this->handlerShouldBeQueued($this->class)) {
            $this->queueHandler($args);
            return true;
        }
        if (empty($this->function)) {
            return $this->_runWithClass($args);
        }
        $class = $this->class;
        $instance = new $class;
        return static::callFunc(array($instance, $this->function), $args);
    }

    private function _runWithClass($args) {
        $class = $this->class;
        return new $class($args);
    }

    private function _runWithFunction($args) {
        if (empty($this->function)) {
            return false;
        }
        if (is_callable($this->function)) {
            return static::callFunc($this->function, $args);
        }
        return false;
    }

    protected function queueHandler(array $args) {
        if (empty($this->function)) {
            $this->function = 'handle';
        }
        $arguments = array_map(function ($a) {
            return is_object($a) ? clone $a : $a;
        }, $args);
        if (!$this->handlerWantsToBeQueued($this->class, $arguments)) {
            return;
        }
        list($listener, $job) = $this->createListenerAndJob($this->class, $this->function, $arguments);

        $connection = QueueManager::connection(
            $listener->connection ?? null
        );

        $queue = $listener->queue ?? null;
        isset($listener->delay)
            ? $connection->laterOn($queue, $listener->delay, $job)
            : $connection->pushOn($queue, $job);
    }

    protected function createListenerAndJob($class, $method, $arguments)
    {
        $listener = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        return [$listener, $this->propagateListenerOptions(
            $listener, new CallQueuedListener($class, $method, $arguments)
        )];
    }

    protected function propagateListenerOptions($listener, $job)
    {
        $job->tries = $listener->tries ?? null;
        $job->timeout = $listener->timeout ?? null;
        $job->timeoutAt = method_exists($listener, 'retryUntil')
            ? $listener->retryUntil() : null;
        return $job;
    }

    protected function handlerWantsToBeQueued($class, $arguments)
    {
        if (method_exists($class, 'shouldQueue')) {
            return app($class)->shouldQueue($arguments[0]);
        }

        return true;
    }

    protected function handlerShouldBeQueued($class) {
        try {
            return (new \ReflectionClass($class))->implementsInterface(
                ShouldQueue::class
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function callFunc($func, $args) {
        if (is_array($args)) {
            return call_user_func_array($func, $args);
        }
        return call_user_func($func, $args);
    }
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

use Zodream\Infrastructure\Queue\QueueManager;
use Zodream\Infrastructure\Queue\ShouldQueue;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:49
 */
class ListenerAction {
    protected mixed $class;
    protected mixed $function;
    protected mixed $file;

    public function __construct($class, $function = null, $file = null) {
        if (is_null($function) && is_string($class) && strpos($class, '@')) {
            list($class, $function) = explode('@', $class, 2);
        }
        $this->class = $class;
        $this->function = $function;
        if (!empty($file) && !is_file($file)) {
            $file = (string)app_path($file);
        }
        $this->file = $file;
    }

    public function __invoke(array $args = array()): mixed {
        if (is_callable($this->class)) {
            return self::callFunc($this->class, $args);
        }
        if (!str_contains($this->class, '::') &&
            !class_exists($this->class) && !function_exists($this->function)) {
            return require($this->file);
        }
        if (empty($this->class)) {
            return $this->invokeWithFunction($args);
        }
        if (!class_exists($this->class)) {
            return null;
        }
        if ($this->handlerShouldBeQueued($this->class)) {
            $this->queueHandler($args);
            return null;
        }
        if (empty($this->function)) {
            return $this->invokeWithClass($args);
        }
        $class = $this->class;
        $instance = new $class;
        return static::callFunc(array($instance, $this->function), $args);
    }

    private function invokeWithClass(array $args) {
        $class = $this->class;
        return new $class(...$args);
    }

    private function invokeWithFunction(array $args): mixed {
        if (empty($this->function)) {
            return null;
        }
        if (is_callable($this->function)) {
            return static::callFunc($this->function, $args);
        }
        return null;
    }

    protected function queueHandler(array $args): void {
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
            $listener->connection ?? ''
        );

        $queue = $listener->queue ?? '';
        isset($listener->delay)
            ? $connection->laterOn($queue, $listener->delay, $job)
            : $connection->pushOn($queue, $job);
    }

    protected function createListenerAndJob($class, $method, $arguments): array {
        $listener = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        return [$listener, $this->propagateListenerOptions(
            $listener, new CallQueuedListener($class, $method, $arguments)
        )];
    }

    protected function propagateListenerOptions($listener, $job) {
        $job->tries = $listener->tries ?? null;
        $job->timeout = $listener->timeout ?? null;
        $job->timeoutAt = method_exists($listener, 'retryUntil')
            ? $listener->retryUntil() : null;
        return $job;
    }

    protected function handlerWantsToBeQueued($class, array $arguments) {
        if (method_exists($class, 'shouldQueue')) {
            return (is_string($class) ? app($class) : $class)->shouldQueue($arguments[0]);
        }
        return true;
    }

    protected function handlerShouldBeQueued(object|string $class) {
        try {
            return (new \ReflectionClass($class))->implementsInterface(
                ShouldQueue::class
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function callFunc(array|callable $func, mixed $args) {
        if (is_array($args)) {
            return call_user_func_array($func, $args);
        }
        return call_user_func($func, $args);
    }
}
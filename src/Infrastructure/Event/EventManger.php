<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

use Zodream\Helpers\Str;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:13
 */

class EventManger {

    /**
     * @var Event[]
     */
    protected array $listeners = [];

    /**
     * @var array [$name => $event]
     */
    protected array $actionNames = [];

    protected array $handlers  = [];

    /**
     * 获取已经注册的事件名
     * @return array
     */
    public function getEventName() {
        return array_keys($this->listeners);
    }

    /**
     * @param string $event 注册事件
     * @param string $class
     * @param int|string|\Closure $function
     * @param string $file
     * @param int $priority
     */
    public function add(string $event, $class, $function = 10, $file = null, $priority = 10) {
        if (!isset($this->listeners[$event]) || !($this->listeners[$event] instanceof Event)) {
            $this->listeners[$event] = new Event();
        }
        $this->listeners[$event]->add($class, $function, $file, $priority);
    }

    public function run($event, $args = array()) {
        if (!isset($this->listeners[$event]) ||
            !($this->listeners[$event] instanceof Event)) {
            return;
        }
        $this->listeners[$event]->run($args);
    }

    public function getListeners($eventName) {
        $listeners = isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];
        $listeners = array_merge(
            $listeners, $this->getWildcardListeners($eventName)
        );

        return class_exists($eventName, false)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : $listeners;
    }

    protected function getWildcardListeners($eventName) {
        $wildcards = [];
        foreach ($this->listeners as $key => $listeners) {
            if (Str::contains($key, '*') && Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }
        return $wildcards;
    }

    protected function addInterfaceListeners($eventName, array $listeners = []) {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }
        return $listeners;
    }

    /**
     * 删除某个
     * @param string $name 根据名称删除
     */
    public function delete($name) {
        if (!isset($this->actionNames[$name])) {
            return;
        }
        $this->listeners[$this->actionNames[$name]]->delete($name);
    }

    /**
     * 执行某个事件
     * @param string $event
     * @param array $payload
     */
    public function dispatch($event = null, $payload = []) {
        list($event, $payload) = $this->parseEventAndPayload(
            $event, $payload
        );
        $this->run($event, $payload);
    }

    public function dispatchNow($event = null, $payload = []) {
        if (is_object($event)) {
            return Action::callFunc([$event, 'handle'], $payload);
        }
        return (new Action($event, 'handle'))->run($payload);
    }

    protected function parseEventAndPayload($event, $payload) {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }
        return [$event, ! is_array($payload) ? [$payload] : $payload];
    }

    public function listen(array|string $events, $listener) {
        foreach ((array) $events as $event) {
            $this->add($event, $listener);
        }
        return $this;
    }

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command) {
        return array_key_exists(get_class($command), $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command) {
        if ($this->hasCommandHandler($command)) {
            return app($this->handlers[get_class($command)]);
        }

        return false;
    }
}
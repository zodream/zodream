<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

use Closure;
use Zodream\Helpers\Str;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:13
 */

class EventManger {

    /**
     * @var ListenerBag[]
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
    public function getEventName(): array {
        return array_keys($this->listeners);
    }

    /**
     * @param string $event 注册事件
     * @param string $class
     * @param int|string|\Closure $function
     * @param string|null $file
     * @param int $priority
     */
    public function add(string $event, mixed $class, int|string|Closure $function = 10,
                        ?string $file = null, int $priority = 10): void {
        if (!isset($this->listeners[$event]) || !($this->listeners[$event] instanceof ListenerBag)) {
            $this->listeners[$event] = new ListenerBag();
        }
        $this->listeners[$event]->add($class, $function, $file, $priority);
    }

    public function getListeners(string $eventName): ListenerBag {
        $items = [];
        if (isset($this->listeners[$eventName])) {
            $items[] = $eventName;
        }
        $items = array_merge(
            $items, $this->getWildcardListeners($eventName)
        );
        if (class_exists($eventName, false)) {
            $items = array_merge($items, $this->addInterfaceListeners($eventName));
        }
        if (count($items) === 1) {
            return $this->listeners[$items[0]];
        }
        $bag = new ListenerBag();
        foreach ($items as $item) {
            $this->listeners[$item]->copyTo($bag);
        }
        return $bag;
    }

    /**
     * 模糊匹配
     * @param string $eventName
     * @return array
     */
    protected function getWildcardListeners(string $eventName): array {
        $wildcards = [];
        foreach ($this->listeners as $key => $listeners) {
            if (Str::contains($key, '*') && Str::is($key, $eventName)) {
                $wildcards[] = $key;
            }
        }
        return $wildcards;
    }

    /**
     * 根据接口获取可能的事件
     * @param $eventName
     * @return array
     */
    protected function addInterfaceListeners($eventName): array {
        $items = [];
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                $items[] = $interface;
            }
        }
        return $items;
    }

    /**
     * 删除某个
     * @param string $name 根据名称删除
     */
    public function delete(string $name): void {
        if (!isset($this->actionNames[$name])) {
            return;
        }
        $this->listeners[$this->actionNames[$name]]->delete($name);
    }

    /**
     * 执行某个事件
     * @param object|string $event
     * @param array $payload
     */
    public function dispatch(object|string $event, array $payload = []): void {
        list($event, $payload) = $this->parseEventAndPayload(
            $event, $payload
        );
        $listeners = $this->getListeners($event);
        $listeners($payload);
    }

    /**
     * 执行事件并获取返回值
     * @param object|string $event
     * @param array $payload 多个参数，第一个参数会进行更新替换
     * @return mixed
     */
    public function filter(object|string $event, array $payload = []): mixed {
        list($event, $payload) = $this->parseEventAndPayload(
            $event, $payload
        );
        $listeners = $this->getListeners($event);
        return $listeners($payload, true);
    }

    public function dispatchNow(object|string $event, array $payload = []): void {
        if (is_object($event)) {
            ListenerAction::callFunc([$event, 'handle'], $payload);
        } else {
            (new ListenerAction($event, 'handle'))($payload);
        }
    }

    protected function parseEventAndPayload(object|string $event, array $payload): array {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }
        return [$event, ! is_array($payload) ? [$payload] : $payload];
    }

    public function listen(array|string $events, mixed $listener): static {
        foreach ((array)$events as $event) {
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
    public function hasCommandHandler(object $command): bool {
        return array_key_exists(get_class($command), $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler(object $command): mixed {
        if ($this->hasCommandHandler($command)) {
            return app($this->handlers[get_class($command)]);
        }
        return false;
    }
}
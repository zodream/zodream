<?php
namespace Zodream\Infrastructure\Event;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:13
 */

class EventManger {

    protected $canAble = true;
    protected $events = array();

    /**
     * @var array [$name => $event]
     */
    protected $actionNames = array();

    /**
     * @param bool $canAble
     */
    public function setCanAble($canAble) {
        $this->canAble = $canAble;
        return $this;
    }

    /**
     * 获取已经注册的事件名
     * @return array
     */
    public function getEventName() {
        return array_keys($this->events);
    }

    /**
     * @param string $event 注册事件
     * @param string $class
     * @param int|string|\Closure $function
     * @param string $file
     * @param int $priority
     */
    public function add($event, $class, $function = 10, $file = null, $priority = 10) {
        if (!$this->canAble) {
            return;
        }
        if (!isset($this->events[$event]) || !($this->events[$event] instanceof Event)) {
            $this->events[$event] = new Event();
        }
        $this->events[$event]->add($class, $function, $file, $priority);
    }

    public function run($event, $args = array()) {
        if (!$this->canAble ||
            !isset($this->events[$event]) ||
            !($this->events[$event] instanceof Event)) {
            return;
        }
        $this->events[$event]->run($args);
    }


    /**
     * 删除某个
     * @param string $name 根据名称删除
     */
    public function delete($name) {
        if (!isset($this->actionNames[$name])) {
            return;
        }
        $this->events[$this->actionNames[$name]]->delete($name);
    }

    /**
     * 执行某个事件
     * @param string $event
     * @param array $args
     */
    public function dispatch($event = null, $payload = []) {
        list($event, $payload) = $this->parseEventAndPayload(
            $event, $payload
        );
        $this->run($event, $payload);
    }

    protected function parseEventAndPayload($event, $payload) {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }
        return [$event, ! is_array($payload) ? [$payload] : $payload];
    }

    public function listen($events, $listener) {
        foreach ((array) $events as $event) {
            $this->add($event, $listener);
        }
    }
}
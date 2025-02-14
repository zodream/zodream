<?php
namespace Zodream\Infrastructure\Concerns;

defined('DEFAULT_EVENT') || define('DEFAULT_EVENT', '__default__');

trait EventTrait {


    /**
     * @var callable[]
     */
    protected $events = [];

    /**
     * 监听事件允许多个
     * @param string|array $events
     * @param callable $callback
     * @return $this
     */
    public function on($events, callable $callback) {
        foreach ((array)$events as $event) {
            if (!array_key_exists($event, $this->events)) {
                $this->events[$event] = [];
            }
            $this->events[$event][] = $callback;
        }
        return $this;
    }

    /**
     * @param null $event
     * @param array $args
     * @return $this
     */
    public function invoke($event = null, array|null $args = null) {
        if (empty($event) && method_exists($this, 'getEvent')) {
            $event = $this->getEvent();
        }
        if (!array_key_exists($event, $this->events)) {
            return $this->invokeDefault($args);
        }
        foreach ($this->events[$event] as $item) {
            if (!is_callable($item)) {
                continue;
            }
            call_user_func_array($item, $args);
        }
        return $this;
    }

    /**
     * INVOKE THE DEFAULT
     * @param array|null $args
     * @return $this
     */
    public function invokeDefault(array|null $args = null) {
        if (!array_key_exists(DEFAULT_EVENT, $this->events)) {
            return $this;
        }
        return $this->invoke(DEFAULT_EVENT, $args);
    }
}
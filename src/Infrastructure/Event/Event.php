<?php
namespace Zodream\Infrastructure\Event;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:20
 */
class Event {
    /**
     * @var array [$priority => [$name => $action]]
     */
    protected $actions = array();

    public function addAction(Action $action,$priority = 10) {
        if (!isset($this->actions[$priority])) {
            $this->actions[$priority] = array();
        }
        $this->actions[$priority][] =$action;
    }

    /**
     * @param $class
     * @param int|string|\Closure $function 如果 $class 是 Action 则为优先级
     * @param string $file
     * @param int $priority 优先级
     */
    public function add($class, $function = 10, $file = null, $priority = 10) {
        if ($class instanceof Action) {
            $this->addAction($class, $function);
            return;
        }
        if (is_numeric($function)) {
            $function = null;
        }
        $this->addAction(new Action($class, $function, $file), $priority);
    }

    public function run($args = array()) {
        ksort($this->actions);
        foreach ($this->actions as $action) {
            foreach ($action as $item) {
                if ($item instanceof Action) {
                    $item->run($args);
                }
            }
        }
    }

    /**
     * @var array [$name => $priority]
     */
    protected $priorityNames = array();

    public function delete($name) {
        if (!isset($this->priorityNames[$name])) {
            return;
        }
        unset($this->actions[$this->priorityNames[$name]][$name]);
    }
}
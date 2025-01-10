<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

use Closure;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/10
 * Time: 9:20
 */
class ListenerBag {
    /**
     * @var array [$priority => [$action]]
     */
    protected array $actions = array();

    public function addAction(ListenerAction $action, int $priority = 10): void {
        if (!isset($this->actions[$priority])) {
            $this->actions[$priority] = array();
        }
        $this->actions[$priority][] =$action;
    }

    /**
     * @param $class
     * @param int|string|Closure $function 如果 $class 是 Action 则为优先级
     * @param string|null $file
     * @param int $priority 优先级
     */
    public function add(mixed $class, int|string|Closure $function = 10,
                        string|null $file = null, int $priority = 10) {
        if ($class instanceof ListenerAction) {
            $this->addAction($class, $function);
            return;
        }
        if (is_numeric($function)) {
            $function = null;
        }
        $this->addAction(new ListenerAction($class, $function, $file), $priority);
    }

    public function __invoke(array $args = [], bool $isFilter = false): mixed {
        ksort($this->actions);
        if ($isFilter && count($args) === 0) {
            $args[] = null;
        }
        foreach ($this->actions as $action) {
            foreach ($action as $item) {
                if (!($item instanceof ListenerAction)) {
                    continue;
                }
                $value = $item($args);
                if ($isFilter) {
                    $args[0] = $value;
                }
            }
        }
        return $isFilter ? $args[0] : null;
    }

    /**
     * @var array [$name => $priority]
     */
    protected array $priorityNames = array();

    public function delete(string|int $name): void {
        if (!isset($this->priorityNames[$name])) {
            return;
        }
        unset($this->actions[$this->priorityNames[$name]][$name]);
    }

    public function copyTo(ListenerBag $bag): void {
        foreach ($this->actions as $priority => $actions) {
            foreach ($actions as $action) {
                $bag->addAction($action, $priority);
            }
        }
    }
}
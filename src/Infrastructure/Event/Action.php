<?php
namespace Zodream\Infrastructure\Event;
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
        $this->class = $class;
        $this->function = $function;
        if (!empty($file) && !is_file($file)) {
            $file = APP_DIR.'/'.ltrim($file, '/');
        }
        $this->file = $file;
    }

    public function run($args = array()) {
        if (is_callable($this->class)) {
            return $this->_callFunc($this->class, $args);
        }
        if (strpos($this->class, '::') === false &&
            (!class_exists($this->class) || !function_exists($this->function))) {
            return require($this->file);
        }
        if (empty($this->class)) {
            return $this->_runWithFunction($args);
        }
        if (empty($this->function)) {
            return $this->_runWithClass($args);
        }
        if (!class_exists($this->class)) {
            return false;
        }
        $class = $this->class;
        $instance = new $class;
        return $this->_callFunc(array($instance, $this->function), $args);
    }

    private function _runWithClass($args) {
        if (!class_exists($this->class)) {
            return false;
        }
        $class = $this->class;
        return new $class($args);
    }

    private function _runWithFunction($args) {
        if (empty($this->function)) {
            return false;
        }
        if (is_callable($this->function)) {
            return $this->_callFunc($this->function, $args);
        }
        return false;
    }

    private function _callFunc($func, $args) {
        if (is_array($args)) {
            return call_user_func_array($func, $args);
        }
        return call_user_func($func, $args);
    }
}
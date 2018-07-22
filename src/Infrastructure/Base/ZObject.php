<?php
namespace Zodream\Infrastructure\Base;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/20
 * Time: 12:36
 */
use Zodream\Infrastructure\Interfaces\ArrayAble;

abstract class ZObject implements ArrayAble {
    /**
     * @param array|ArrayAble $args
     * @return $this
     */
    public function parse($args) {
        if ($args instanceof ArrayAble) {
            $args = $args->toArray();
        }
        foreach ((array)$args as $key => $item) {
            if (property_exists($this, $key)) {
                $this->$key = $item;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() {
        return array();
    }
}
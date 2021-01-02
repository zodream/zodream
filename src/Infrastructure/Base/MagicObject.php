<?php 
namespace Zodream\Infrastructure\Base;
/**
* object 的扩展
* 主要增加get、set、has 方法，及使用魔术变量
* 
* @author Jason
*/
use ArrayIterator;
use ArrayAccess;
use IteratorAggregate;
use Zodream\Infrastructure\Contracts\ArrayAble;
use Zodream\Infrastructure\Contracts\JsonAble;
use Zodream\Helpers\Json;
use Zodream\Infrastructure\Concerns\Attributes;
use JsonSerializable;

class MagicObject extends ZObject implements ArrayAccess, JsonAble, IteratorAggregate, JsonSerializable {
	
	use Attributes;



    /**
     * 合并数组并返回新数组
     * @param array $data
     * @return array
     */
	public function merge(array $data) {
	    return array_merge($this->__attributes, $data);
    }

	/**
	 * 如果$key不存在则继续寻找下一个,默认是作为key寻找，支持 @值
	 * @param $key
	 * @param $default
	 * @return array|string
	 */
	public function getWithDefault($key, $default) {
		$args = func_get_args();
		foreach ($args as $arg) {
			if (strpos($arg, '@') !== false) {
				return substr($arg, 1);
			}
			if ($this->hasAttribute($arg)) {
				return $this->getAttribute($arg);
			}
		}
		return null;
	}

	public function offsetExists($offset) {
		return $this->hasAttribute($offset);
	}

	public function offsetGet($offset) {
		return $this->getAttribute($offset);
	}

	public function offsetSet($offset, $value) {
		$this->setAttribute($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->deleteAttribute($offset);
	}

    /**
     * Count
     *
     * @see https://secure.php.net/manual/en/countable.count.php
     *
     * @param void
     *
     * @return int The number of elements stored in the Array.
     *
     * @access public
     */
    public function count() {
        return (int) count($this->__attributes);
    }

    /**
     * Current
     *
     * @see https://secure.php.net/manual/en/iterator.current.php
     *
     * @param void
     *
     * @return mixed Data at the current position.
     *
     * @access public
     */
    public function current() {
        return current($this->__attributes);
    }

    /**
     * Next
     *
     * @see https://secure.php.net/manual/en/iterator.next.php
     *
     * @param void
     *
     * @return void
     *
     * @access public
     */
    public function next() {
        next($this->__attributes);
    }

    /**
     * Key
     *
     * @see https://secure.php.net/manual/en/iterator.key.php
     *
     * @param void
     *
     * @return mixed Case-Sensitive key at current position.
     *
     * @access public
     */
    public function key() {
        return key($this->__attributes);
    }

    /**
     * Valid
     *
     * @see https://secure.php.net/manual/en/iterator.valid.php
     *
     * @return bool If the current position is valid.
     *
     * @access public
     */
    public function valid() {
        return (bool) !(key($this->__attributes) === null);
    }

    /**
     * Rewind
     *
     * @see https://secure.php.net/manual/en/iterator.rewind.php
     *
     * @param void
     *
     * @return void
     *
     * @access public
     */
    public function rewind() {
        reset($this->__attributes);
    }

	/**
	 * 允许使用 foreach 直接执行
	 */
	public function getIterator() {
		return new ArrayIterator($this->toArray());
	}

	public function parse($args) {
        return $this->setAttribute($args);
    }

    public function toArray() {
        return $this->getAttribute();
    }

    public function jsonSerialize() {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof JsonAble) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof ArrayAble) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->toArray());
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE) {
        return Json::encode($this->jsonSerialize(), $options);
    }
}
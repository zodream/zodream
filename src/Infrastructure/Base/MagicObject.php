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
use Zodream\Infrastructure\Interfaces\JsonAble;
use Zodream\Infrastructure\ObjectExpand\ArrayExpand;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;

class MagicObject extends ZObject implements ArrayAccess, JsonAble, IteratorAggregate {
	
	protected $_data = array();

	/**
	 * 获取值
	 * @param string $key 关键字
	 * @param string $default 默认返回值
	 * @return array|string
	 */
	public function get($key = null, $default = null) {
		if (empty($key)) {
			return $this->_data;
		}
		if (!is_array($this->_data)) {
			$this->_data = (array)$this->_data;
		}
		if ($this->has($key)) {
			return $this->_data[$key];
		}
		if (strpos($key, ',') !== false) {
			$result = ArrayExpand::getValues($key, $this->_data, $default);
		} else {
			$result = ArrayExpand::getChild($key, $this->_data, is_object($default) ? null : $default);
		}
		if (is_object($default)) {
			return $default($result);
		}
		return $result;
	}

    /**
     * 合并数组并返回新数组
     * @param array $data
     * @return array
     */
	public function merge(array $data) {
	    return array_merge($this->_data, $data);
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
			if ($this->has($arg)) {
				return $this->get($arg);
			}
		}
		return null;
	}

	/**
	 * 设置值
	 * @param string|array $key
	 * @param string $value
	 * @return $this
	 */
	public function set($key, $value = null) {
		if (is_object($key)) {
			$key = (array)$key;
		}
		if (is_array($key)) {
			$this->_data = array_merge($this->_data, $key);
			return $this;
		}
		if (empty($key)) {
			return $this;
		}
		$this->_data[$key] = $value;
		return $this;
	}
	
	/**
	 * 删除键 目前只支持一维
	 * @param string $tag
	 */
	public function del($tag) {
		foreach (func_get_args() as $value) {
			unset($this->_data[$value]);
		}
	}

	public function clear() {
		$this->_data = array();
	}

	/**
	 * 判断是否有
	 * @param string|null $key 如果为null 则判断是否有数据
	 * @return bool
	 */
	public function has($key = null) {
		if (is_null($key)) {
			return !empty($this->_data);
		}
		if (empty($this->_data)) {
			return false;
		}
		return array_key_exists($key, $this->_data);
	}
	
	public function __get($key) {
		return $this->get($key);
	}
	
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	public function offsetExists($offset) {
		return $this->has($offset);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->del($offset);
	}

	/**
	 * 允许使用 foreach 直接执行
	 */
	public function getIterator() {
		return new ArrayIterator($this->get());
	}

	public function parse($args) {
        return $this->set($args);
    }

    public function toArray() {
        return $this->get();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE) {
        return JsonExpand::encode($this->toArray(), $options);
    }
}
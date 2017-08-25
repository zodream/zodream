<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 缓存基类
* 
* @author Jason
*/
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Infrastructure\ObjectExpand\StringExpand;

abstract class Cache extends ConfigObject implements \ArrayAccess {

	/**
	 * gc自动执行的几率 0-1000000；
	 * @var int
	 */
    protected $configs = [
        'gc' => 10
    ];

    protected $configKey = 'cache';

    protected function getGC() {
        return $this->configs['gc'];
    }
	
	public function filterKey($key) {
		if (is_string($key)) {
			return ctype_alnum($key) && StringExpand::byteLength($key) <= 32 ? $key : md5($key);
		}
		return md5(json_encode($key));
	}
	
	public function get($key) {
		return $this->getValue($this->filterKey($key));
	}

    /**
     * SET CACHE
     * @param string $key
     * @param string $value
     * @param int $duration
     */
	public function set($key, $value = null, $duration = null) {
		if (is_array($key) && null === $value && null === $duration) {
			foreach ($key as $k => $v) {
				$this->setValue($this->filterKey($k), $v[0], $v[1]);
			}
		} else {
			$this->setValue($key, $value, $duration);
		}
	}
	
	public function add($key, $value, $duration) {
		return $this->addValue($this->filterKey($key), $value, $duration);
	}
	
	public function has($key) {
		return $this->hasValue($this->filterKey($key));
	}
	
	public function delete($key = null) {
		if (null === $key) {
			return $this->clearValue();
		}
		return $this->deleteValue($this->filterKey($key));
	}
	
	abstract protected function getValue($key);
	
	abstract protected function setValue($key, $value, $duration);
	
	abstract protected function addValue($key, $value, $duration);
	
	protected function hasValue($key) {
        return $this->getValue($key) !== false;
	}
	
	abstract protected function deleteValue($key);
	
	abstract protected function clearValue();
	
	public function offsetExists($key) {
		return $this->has($key);
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	public function offsetGet($key) {
		return $this->get($key);
	}

	/**
	 * @param string $key
	 * @param string|array $value
	 */
	public function offsetSet($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * @param string $key
	 * @internal param $offset
	 */
	public function offsetUnset($key) {
		$this->delete($key);
	}
}

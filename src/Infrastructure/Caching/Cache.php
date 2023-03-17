<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存基类
* 
* @author Jason
*/
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Helpers\Str;
use Exception;
use Zodream\Infrastructure\Contracts\Cache as CacheInterface;

abstract class Cache extends ConfigObject implements CacheInterface, \ArrayAccess {

	/**
	 * gc自动执行的几率 0-1000000；
	 * @var int
	 */
    protected array $configs = [
        'gc' => 10,
        'serializer' => null,
        'keyPrefix' => ''
    ];

    protected $configKey = 'cache';

    protected function getGC() {
        return $this->configs['gc'];
    }

    /**
     * 子缓存区
     * @param $store
     * @return Cache
     */
    public function store(string $store): CacheInterface {
        $newCache = clone $this;
        $newCache->setConfigs([
            'keyPrefix' => $store
        ]);
        return $newCache;
    }
	
	public function filterKey($key) {
		if (is_string($key)) {
			return $this->configs['keyPrefix'].
                (ctype_alnum($key) && Str::byteLength($key) <= 32 ? $key : md5($key));
		}
		return $this->configs['keyPrefix'].md5(json_encode($key));
	}

    /**
     * 设置值
     * @param $key
     * @param $callable
     * @param ?int $duration 当前时间加秒数
     * @param Dependency $dependency 设置判断更新的条件
     * @return bool|mixed
     * @throws Exception
     */
    public function getOrSet($key, $callable, $duration = null, $dependency = null) {
        if (($value = $this->get($key)) !== false) {
            return $value;
        }

        $value = call_user_func($callable, $this);
        if (!$this->set($key, $value, $duration, $dependency)) {
            throw new Exception(
                __('Failed to set cache value for key ')
                . json_encode($key));
        }
        return $value;
    }

    /**
     * 获取值
     * @param $key
     * @return bool|mixed
     */
	public function get($key) {
        $key = $this->filterKey($key);
        $value = $this->getValue($key);
        if ($value === false || $this->configs['serializer'] === false) {
            return $value;
        } elseif ($this->configs['serializer'] === null) {
            $value = unserialize($value);
        } else {
            $value = call_user_func($this->configs['serializer'][1], $value);
        }
        if (is_array($value) && !($value[1] instanceof Dependency && $value[1]->isChanged($this))) {
            return $value[0];
        }
        return false;
	}

    /**
     * SET CACHE
     * @param string $key
     * @param string $value
     * @param int $duration
     * @param Dependency $dependency
     * @return static|mixed
     */
	public function set($key, $value = null, $duration = null, $dependency = null) {
		if (is_array($key) && null === $value && null === $duration) {
			foreach ($key as $k => $v) {
				$this->set($k, $v[0],
                    $v[1] ?? $duration,
                    $v[2] ?? $dependency);
			}
			return $this;
		}
        if ($dependency !== null && $this->configs['serializer'] !== false) {
            $dependency->evaluateDependency($this);
        }
        if ($this->configs['serializer'] === null) {
            $value = serialize([$value, $dependency]);
        } elseif ($this->configs['serializer'] !== false) {
            $value = call_user_func($this->configs['serializer'][0], [$value, $dependency]);
        }
        $key = $this->filterKey($key);
        return $this->setValue($key, $value, $duration);
	}
	
	public function add($key, $value, $duration) {
		return $this->addValue($this->filterKey($key), $value, $duration);
	}

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1) {

        return false;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1) {
        return false;
    }
	
	public function has($key): bool {
		return $this->hasValue($this->filterKey($key));
	}
	
	public function delete($key) {
		return $this->deleteValue($this->filterKey($key));
	}

	public function flush()
    {
        return $this->clearValue();
    }

    abstract protected function getValue($key);
	
	abstract protected function setValue($key, $value, $duration);
	
	abstract protected function addValue($key, $value, $duration);
	
	protected function hasValue($key) {
        return $this->getValue($key) !== false;
	}
	
	abstract protected function deleteValue($key);
	
	abstract protected function clearValue();
	
	public function offsetExists($key): bool {
		return $this->has($key);
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	public function offsetGet($key): mixed {
		return $this->get($key);
	}

	/**
	 * @param string $key
	 * @param string|array $value
	 */
	public function offsetSet($key, $value): void {
		$this->set($key, $value);
	}

	/**
	 * @param string $key
	 * @internal param $offset
	 */
	public function offsetUnset($key): void {
		$this->delete($key);
	}
}

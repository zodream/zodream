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
use Zodream\Infrastructure\Contracts\CacheDependency as DependencyInterface;

abstract class Cache extends ConfigObject implements CacheInterface, \ArrayAccess {


    protected array $configs = [
        'gc' => 10, // gc自动执行的几率 0-1000000；
        'serializer' => null,
        'keyPrefix' => ''
    ];

    protected string $configKey = 'cache';

    protected function getGC(): int {
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
	
	public function filterKey(mixed $key): string {
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
     * @param int|null $duration 当前时间加秒数
     * @param Dependency $dependency 设置判断更新的条件
     * @return bool|mixed
     * @throws Exception
     */
    public function getOrSet(mixed $key, callable $callable, int|null $duration = null, DependencyInterface|null $dependency = null) {
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
	public function get(mixed $key) {
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
	public function set(mixed $key, mixed $value = null, int|null $duration = null, DependencyInterface|null $dependency = null) {
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
        return $this->setValue($key, $value, $duration ?? 0);
	}
	
	public function add(mixed $key, mixed $value, int $duration) {
		return $this->addValue($this->filterKey($key), $value, $duration);
	}

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment(mixed $key, int $value = 1) {
        return false;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement(mixed $key, int $value = 1) {
        return false;
    }
	
	public function has(mixed $key): bool {
		return $this->hasValue($this->filterKey($key));
	}
	
	public function delete(mixed $key) {
		return $this->deleteValue($this->filterKey($key));
	}

	public function flush()
    {
        return $this->clearValue();
    }

    abstract protected function getValue(string $key);
	
	abstract protected function setValue(string $key, mixed $value, int $duration);
	
	abstract protected function addValue(string $key, mixed $value, int $duration);
	
	protected function hasValue(string $key): bool {
        return $this->getValue($key) !== false;
	}
	
	abstract protected function deleteValue(string $key);
	
	abstract protected function clearValue();
	
	public function offsetExists(mixed $key): bool {
		return $this->has($key);
	}

	/**
	 * @param string $key
	 * @return array|string
	 */
	public function offsetGet(mixed $key): mixed {
		return $this->get($key);
	}

	/**
	 * @param string $key
	 * @param string|array $value
	 */
	public function offsetSet(mixed $key, mixed $value): void {
		$this->set($key, $value);
	}

	/**
	 * @param string $key
	 * @internal param $offset
	 */
	public function offsetUnset(mixed $key): void {
		$this->delete($key);
	}
}

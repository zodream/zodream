<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ArrayCache extends Cache {
	
	protected array $cache = [];
    protected array $configs = [
        'gc' => 10,
        'serializer' => false,
        'keyPrefix' => ''
    ];
	
	protected function getValue(string $key) {
		if (isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true))) {
			return $this->cache[$key][0];
		} else {
			return false;
		}
	}
	
	protected function setValue(string $key, mixed $value, int $duration) {
		$this->cache[$key] = array(
				$value, 
				$duration === 0 ? 0 : microtime(true) + $duration
		);
	}
	
	protected function addValue(string $key, mixed $value, int $duration) {
		if (isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true))) {
			return false;
		} else {
			$this->cache[$key] = [$value, $duration === 0 ? 0 : microtime(true) + $duration];
			return true;
		}
	}
	
	protected function hasValue(string $key): bool {
		return isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true));
	}
	
	protected function deleteValue(string $key) {
		unset($this->cache[$key]);
		return true;
	}
	
	protected function clearValue() {
		$this->cache = array();
		return true;
	}
}
<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ArrayCache extends Cache {
	
	protected $cache = array();
	
	protected function getValue($key) {
		if (isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true))) {
			return $this->cache[$key][0];
		} else {
			return false;
		}
	}
	
	protected function setValue($key, $value, $duration) {
		$this->cache[$key] = array(
				$value, 
				$duration === 0 ? 0 : microtime(true) + $duration
		);
	}
	
	protected function addValue($key, $value, $duration) {
		if (isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true))) {
			return false;
		} else {
			$this->cache[$key] = [$value, $duration === 0 ? 0 : microtime(true) + $duration];
			return true;
		}
	}
	
	protected function hasValue($key) {
		return isset($this->cache[$key]) 
				&& ($this->cache[$key][1] === 0 
				|| $this->cache[$key][1] > microtime(true));
	}
	
	protected function deleteValue($key) {
		unset($this->cache[$key]);
		return true;
	}
	
	protected function clearValue() {
		$this->cache = array();
		return true;
	}
}
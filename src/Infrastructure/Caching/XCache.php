<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class XCache extends Cache {
	
	protected function getValue($key) {
		return xcache_isset($key) ? xcache_get($key) : false;
	}
	
	protected function setValue($key, $value, $duration) {
		return xcache_set($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		return !xcache_isset($key) ? $this->setValue($key, $value, $duration) : false;
	}
	
	protected function hasValue($key) {
		return xcache_isset($key);
	}
	
	protected function deleteValue($key) {
		return xcache_unset($key);
	}
	
	protected function clearValue() {
		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
            if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
                return false;
            }
        }
        return true;
	}
}
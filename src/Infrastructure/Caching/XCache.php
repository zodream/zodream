<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class XCache extends Cache {
	
	protected function getValue(string $key) {
		return \xcache_isset($key) ? \xcache_get($key) : false;
	}
	
	protected function setValue(string $key, mixed $value, int $duration) {
		return \xcache_set($key, $value, $duration);
	}
	
	protected function addValue(string $key, mixed $value, int $duration) {
		return !\xcache_isset($key) ? $this->setValue($key, $value, $duration) : false;
	}
	
	protected function hasValue(string $key): bool {
		return \xcache_isset($key);
	}
	
	protected function deleteValue(string $key) {
		return \xcache_unset($key);
	}
	
	protected function clearValue() {
		for ($i = 0, $max = \xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
            if (\xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
                return false;
            }
        }
        return true;
	}
}
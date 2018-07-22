<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class WinCache extends Cache {
	
	protected function getValue($key) {
		return wincache_ucache_get($key);
	}
	
	protected function setValue($key, $value, $duration) {
		return wincache_ucache_set($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		return wincache_ucache_add($key, $value, $duration);
	}
	
	protected function hasValue($key) {
		return wincache_ucache_exists($key);
	}
	
	protected function deleteValue($key) {
		return wincache_ucache_delete($key);
	}
	
	protected function clearValue() {
		return wincache_ucache_clear();
	}
}
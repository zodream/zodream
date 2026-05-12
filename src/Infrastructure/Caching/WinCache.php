<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class WinCache extends Cache {
	
	protected function getValue(string $key) {
		return \wincache_ucache_get($key);
	}
	
	protected function setValue(string $key, mixed $value, int $duration) {
		return \wincache_ucache_set($key, $value, $duration);
	}
	
	protected function addValue(string $key, mixed $value, int $duration) {
		return \wincache_ucache_add($key, $value, $duration);
	}
	
	protected function hasValue(string $key): bool {
		return \wincache_ucache_exists($key);
	}
	
	protected function deleteValue(string $key) {
		return \wincache_ucache_delete($key);
	}
	
	protected function clearValue() {
		return \wincache_ucache_clear();
	}
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ZendCache extends Cache {
	protected function getValue(string $key) {
		$result = \zend_shm_cache_fetch($key);
        return $result === null ? false : $result;
	}
	
	protected function setValue(string $key, mixed $value, int $duration) {
		return \zend_shm_cache_store($key, $value, $duration);
	}
	
	protected function addValue(string $key, mixed $value, int $duration) {
		return \zend_shm_cache_fetch($key) === null ? $this->setValue($key, $value, $duration) : false;
	}
	
	protected function deleteValue(string $key) {
		return \zend_shm_cache_delete($key);
	}
	
	protected function clearValue() {
		return \zend_shm_cache_clear();
	}
}
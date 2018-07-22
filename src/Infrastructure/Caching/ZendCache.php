<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ZendCache extends Cache {
	protected function getValue($key) {
		$result = zend_shm_cache_fetch($key);
        return $result === null ? false : $result;
	}
	
	protected function setValue($key, $value, $duration) {
		return zend_shm_cache_store($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		return zend_shm_cache_fetch($key) === null ? $this->setValue($key, $value, $duration) : false;
	}
	
	protected function deleteValue($key) {
		return zend_shm_cache_delete($key);
	}
	
	protected function clearValue() {
		return zend_shm_cache_clear();
	}
}
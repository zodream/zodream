<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ApcCache extends Cache {

    const string APCU = 'apcu';
    const string APC = 'apc';

    protected array $configs = ['extension' => self::APC];

	protected function isAPc(): bool {
	    return $this->configs['extension'] === self::APC;
    }
	
	protected function getValue($key) {
		return $this->isAPc() ? apc_fetch($key) : apcu_fetch($key);
	}
	
	protected function setValue($key, $value, $duration) {
        $this->isAPc() ? apc_store($key, $value, $duration) : apcu_store($key, $value, $duration);
	}
	
	protected function addValue($key, $value, $duration) {
		return $this->isAPc() ? apc_add($key, $value, $duration) : apcu_add($key, $value, $duration);
	}
	
	protected function hasValue($key) {
		return $this->isAPc() ? apc_exists($key) : apcu_exists($key);
	}
	
	protected function deleteValue($key) {
		return $this->isAPc() ? apc_delete($key) : apcu_delete($key);
	}
	
	protected function clearValue() {
		return $this->isAPc() ? apc_clear_cache('user') : apcu_clear_cache();
	}
}
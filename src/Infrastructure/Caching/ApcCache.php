<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Caching;
/**
* 缓存类
* 
* @author Jason
*/

class ApcCache extends Cache {

    const APCU = 'apcu';
    const APC = 'apc';

    protected array $configs = ['extension' => self::APC];

	protected function isAPc(): bool {
	    return $this->configs['extension'] === self::APC;
    }
	
	protected function getValue(string $key) {
		return $this->isAPc() ? \apc_fetch($key) : \apcu_fetch($key);
	}
	
	protected function setValue(string $key, mixed $value, int $duration) {
        $this->isAPc() ? \apc_store($key, $value, $duration) : \apcu_store($key, $value, $duration);
	}
	
	protected function addValue(string $key, mixed $value, int $duration) {
		return $this->isAPc() ? \apc_add($key, $value, $duration) : \apcu_add($key, $value, $duration);
	}
	
	protected function hasValue(string $key): bool {
		return $this->isAPc() ? \apc_exists($key) : \apcu_exists($key);
	}
	
	protected function deleteValue(string $key) {
		return $this->isAPc() ? \apc_delete($key) : \apcu_delete($key);
	}
	
	protected function clearValue() {
		return $this->isAPc() ? \apc_clear_cache('user') : \apcu_clear_cache();
	}
}
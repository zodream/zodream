<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

use Closure;
use Zodream\Disk\Directory;
use ReflectionFunction;

final class Lock {

    private array $_items = [];
    private readonly Directory $directory;

    public function __construct() {
        $this->directory = app_path()->childDirectory(config('cache.directory', 'data/cache/'));
    }

    private function openFile(string $key): mixed {
        if (isset($this->_items[$key]) && is_resource($this->_items[$key])) {
            return $this->_items[$key];
        }
        return $this->_items[$key] = fopen($this->directory->file(sha1($key)).'.lock', 'c');
    }
    /**
     * 加锁，或临时加锁
     * @param string|Closure $key 匿名方法为临时加锁，执行往后解除
     */
    public function lock(string|Closure $key): void {
        if (is_string($key)) {
            if (!flock($this->openFile($key), \LOCK_EX)) {
                throw new LockException(__('Failed to lock the file'));
            }
            return;
        }
        $ref = new ReflectionFunction($key);
        $id = $ref->getFileName() . ':' . $ref->getEndLine();
        if ($this->tryLock($id)) {
            call_user_func($key);
            $this->unlock($id);
        }

    }
    /**
     * 解除锁定
     * @param string $key
     */
    public function unlock(string $key): void {
        if ($this->_items[$key]) {
            flock($this->_items[$key], \LOCK_UN);
            unset($this->_items[$key]);
        }
    }
    /**
     * 尝试加锁
     * @param string $key
     * @return bool 是否加锁成功
     */
    public function tryLock(string $key): bool {
        return flock($this->openFile($key), \LOCK_EX);
    }
}
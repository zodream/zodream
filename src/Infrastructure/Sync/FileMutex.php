<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

use Closure;

class FileMutex implements LockMutex {

    private mixed $handle;

    public function __construct(
        mixed $fileHandle
    )
    {
        if (!is_resource($fileHandle)) {
            throw new \InvalidArgumentException(__('The file handle is not a valid resource'));
        }
        $this->handle = $fileHandle;
    }


    public function synchronized(callable $fx): mixed {
        $this->lock();
        $fxResult = null;
        $fxException = null;
        try {
            $fxResult = $fx();
        } catch (\Throwable $fxException) {
            throw $fxException;
        } finally {
            $this->unlock();
        }

        return $fxResult;
    }

    public function lock(): void {
        if (!flock($this->handle, \LOCK_EX)) {
            throw new LockException(__('Failed to lock the file'));
        }
    }
    public function unlock(): void {
        if (!flock($this->handle, \LOCK_UN)) {
            throw new LockException(__('Failed to unlock the file'));
        }
    }
}
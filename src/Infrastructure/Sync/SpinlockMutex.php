<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

interface SpinlockMutex extends LockMutex {
    public function acquire(string $key): bool;
    public function release(string $key): bool;
}
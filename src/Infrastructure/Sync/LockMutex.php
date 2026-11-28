<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

interface LockMutex extends Mutex {
    public function lock(): void;
    public function unlock(): void;
}
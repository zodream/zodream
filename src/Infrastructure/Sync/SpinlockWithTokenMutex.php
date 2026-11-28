<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

interface SpinlockWithTokenMutex extends SpinlockMutex {
    public function acquireWithToken(string $key, float $expireTimeout): string;
    public function releaseWithToken(string $key, string $token): bool;
}
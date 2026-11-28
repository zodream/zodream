<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

interface Mutex {

    /**
     * @template T
     * @param callable(): T $fx
     * @return T
     */
    public function synchronized(callable $fx): mixed;
}
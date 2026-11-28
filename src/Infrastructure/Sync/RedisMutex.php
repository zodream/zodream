<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Sync;

use Predis\ClientInterface;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Support\Loop;

class RedisMutex implements SpinlockWithTokenMutex {

    private readonly string $key;
    private ?float $acquireTs = null;
    private ?string $token = null;

    public function __construct(
        private readonly ClientInterface $client, 
        string $name, 
        private readonly float $acquireTimeout = 3, 
        private readonly float $expireTimeout = \INF
    )
    {
        $this->key = 'zre-lock:'.$name;
    }

    /**
     * @template T
     * @param callable(): T $fx
     * @return T
     */
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
        $loop = new Loop();

        $loop->execute(function () use ($loop): void {
            if ($this->acquire($this->key)) {
                $loop->end();
            }
        }, $this->acquireTimeout);
    }
    public function unlock(): void {
        if (!$this->release($this->key)) {
            throw new LockException(__('Failed to release the lock'));
        }
    }

    public function acquire(string $key): bool {
        $acquireTs = microtime(true);

        $token = $this->acquireWithToken($key, $this->expireTimeout);

        if ($token === false) {
            return false;
        }

        $this->acquireTs = $acquireTs;
        $this->token = $token;

        return true;
    }
    public function release(string $key): bool {
        try {
            return $this->releaseWithToken($key, $this->token);
        } finally {
            try {
                $elapsedTime = microtime(true) - $this->acquireTs;
                if ($elapsedTime >= $this->expireTimeout) {
                    throw new LockException(__('Excute timeout'));
                }
            } finally {
                $this->token = null;
                $this->acquireTs = null;
            }
        }
    }

    public function acquireWithToken(string $key, float $expireTimeout): string {
        $token = Str::random(10);
        $expireTimeoutMillis = (int)round(ceil($expireTimeout * 1000));
        return $this->client->set($key, $token, 'PX', $expireTimeoutMillis, 'NX') !== null
            ? $token
            : false;
    }
    public function releaseWithToken(string $key, string $token): bool {
        $script = <<<LUA
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
            LUA;
        return (int)$this->client->eval($script, 1, $key, $token) === 1;
    }
}
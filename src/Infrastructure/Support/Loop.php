<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


final class Loop {
    private const MINIMUM_WAIT_US = 10_000;
    private const MAXIMUM_WAIT_US = 500_000;

    private bool $looping = false;

    public function end(): void {
        $this->looping = false;
    }

    /**
     * 
     * @template T
     * 
     * @param callable(): T $cb
     * @param float $timeout
     * @return T 
     */
    public function execute(callable $cb, float $timeout) {
        $this->looping = true;
        $deadlineTs = microtime(true) + $timeout;

        $result = null;
        for ($i = 0; $this->looping && microtime(true) < $deadlineTs; ++$i) { 
            $result = call_user_func($cb);
            if (!$this->looping) {
                return $result;
            }
            $usecRemaining = (int) round(($deadlineTs - microtime(true)) * 1e6);
            if ($usecRemaining <= 0) {
                break;
            }
            $min = min(
                self::MINIMUM_WAIT_US * 1.25 ** $i,
                self::MAXIMUM_WAIT_US
            );
            $max = min($min * 2, self::MAXIMUM_WAIT_US);
            $usecToSleep = min($usecRemaining, random_int((int) $min, (int) $max));
            usleep($usecToSleep);
        }
    }
}
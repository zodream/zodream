<?php
namespace Zodream\Infrastructure\Event;
/*
 * This file is part of Evenement.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface EventEmitterInterface {
    public function on($event, callable $listener): void;
    public function once($event, callable $listener): void;
    public function removeListener($event, callable $listener): void;
    public function removeAllListeners($event = null): void;
    public function listeners($event): array;
    public function emit($event, array $arguments = []): void;
}
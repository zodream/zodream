<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Event;

trait DispatchAble
{
    /**
     * Dispatch the event with the given arguments.
     *
     * @return void
     */
    public static function dispatch(...$items): void {
        event(new static(...$items));
    }
}
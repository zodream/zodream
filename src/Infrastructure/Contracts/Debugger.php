<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Debugger {
    public function exceptionHandler($exception, $exit = true);
}
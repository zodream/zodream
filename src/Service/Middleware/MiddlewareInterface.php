<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;

interface MiddlewareInterface {

    public function handle($payload, callable $next);
}
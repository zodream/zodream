<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;

interface MiddlewareInterface {

    public function handle(HttpContext $context, callable $next);
}
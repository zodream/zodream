<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;

use Zodream\Infrastructure\Contracts\HttpContext;

class AuthMiddleware implements MiddlewareInterface {

    public function handle(HttpContext $context, callable $next) {
        return $next($context);
    }
}
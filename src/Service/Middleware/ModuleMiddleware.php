<?php
declare(strict_types=1);

namespace Zodream\Service\Middleware;


class ModuleMiddleware implements MiddlewareInterface {

    public function handle($payload, callable $next) {
        return $next($payload);
    }
}